const http = require('http');
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');
const os = require('os');

const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const PORT = 9455;
const BASE = 'http://localhost/zaffran/';
const OUT = path.join(__dirname, 'screenshots', 'upgrade');
fs.mkdirSync(OUT, { recursive: true });

function wait(ms) { return new Promise(r => setTimeout(r, ms)); }
function getJson(url) {
  return new Promise((resolve, reject) => {
    http.get(url, res => {
      let d = '';
      res.on('data', c => d += c);
      res.on('end', () => resolve(JSON.parse(d)));
    }).on('error', reject);
  });
}

async function main() {
  const tempDir = fs.mkdtempSync(path.join(os.tmpdir(), 'chrome-qa-'));
  const chrome = spawn(CHROME, [
    '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
    '--hide-scrollbars', '--force-device-scale-factor=1',
    '--user-data-dir=' + tempDir, '--remote-debugging-port=' + PORT,
    'about:blank'
  ]);

  let tabs = null;
  for (let i = 0; i < 40; i++) {
    await wait(250);
    try { tabs = await getJson(`http://127.0.0.1:${PORT}/json`); break; } catch (e) {}
  }
  if (!tabs) { console.error('Chrome failed'); chrome.kill(); process.exit(1); }

  const pageTab = tabs.find(t => t.type === 'page');
  const ws = new WebSocket(pageTab.webSocketDebuggerUrl);
  await new Promise(res => ws.addEventListener('open', res));
  let msgId = 0;
  function send(method, params = {}) {
    return new Promise(resolve => {
      const id = ++msgId;
      const handler = ev => {
        const msg = JSON.parse(ev.data);
        if (msg.id === id) { ws.removeEventListener('message', handler); resolve(msg.result); }
      };
      ws.addEventListener('message', handler);
      ws.send(JSON.stringify({ id, method, params }));
    });
  }

  const consoleErrors = [];
  ws.addEventListener('message', ev => {
    const m = JSON.parse(ev.data);
    if (m.method === 'Runtime.consoleAPICalled' && m.params.type === 'error') {
      consoleErrors.push(m.params.args.map(a => a.value || a.description || '').join(' ').slice(0, 300));
    }
    if (m.method === 'Runtime.exceptionThrown') {
      consoleErrors.push((m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text || '').slice(0, 300));
    }
  });

  await send('Page.enable');
  await send('Runtime.enable');
  await send('Network.enable');
  if (process.env.REDUCED === '1') {
    await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
  }

  const pages = process.argv[2] ? JSON.parse(process.argv[2]) : [
    { name: 'home', path: 'index.php' },
    { name: 'shop', path: 'shop.php' },
    { name: 'product', path: 'product.php?slug=zaffran-saffron' },
    { name: 'cart', path: 'cart.php' },
    { name: 'login', path: 'login.php' }
  ];
  const widths = process.argv[3] ? JSON.parse(process.argv[3]) : [1440, 390];

  for (const p of pages) {
    for (const w of widths) {
      await send('Emulation.setDeviceMetricsOverride', { width: w, height: w < 768 ? 844 : 900, deviceScaleFactor: 1, mobile: w < 768 });
      if (p.seedCart) {
        await send('Page.navigate', { url: BASE + 'index.php' });
        await wait(1400);
        await send('Runtime.evaluate', {
          awaitPromise: true, returnByValue: true,
          expression: `fetch('cart_api.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},credentials:'same-origin',body:JSON.stringify({action:'add',product_id:${p.seedCart[0]},quantity:${p.seedCart[1]}})}).then(r=>r.json())`
        });
        await wait(400);
      }
      await send('Page.navigate', { url: BASE + p.path });
      await wait(2600);
      if (process.env.ACTION) {
        await send('Runtime.evaluate', { expression: process.env.ACTION, awaitPromise: true });
        await wait(700);
      }
      if (process.env.FULL === '1') {
        await send('Runtime.evaluate', { expression: `(async () => { document.documentElement.style.scrollBehavior = 'auto'; const h = document.documentElement.scrollHeight; for (let y = 0; y < h; y += window.innerHeight * 0.7) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 90)); } window.scrollTo(0, 0); })()` });
        await wait(900);
      }
      const layout = await send('Runtime.evaluate', {
        expression: `(() => {
          const de = document.documentElement;
          const over = de.scrollWidth - de.clientWidth;
          const offenders = [];
          if (over > 0) {
            document.querySelectorAll('body *').forEach(el => {
              const r = el.getBoundingClientRect();
              if (r.right > de.clientWidth + 2 && r.width > 0 && getComputedStyle(el).position !== 'fixed') {
                offenders.push((el.className && typeof el.className === 'string' ? el.tagName + '.' + el.className.split(' ').slice(0,2).join('.') : el.tagName) + ' right=' + Math.round(r.right));
              }
            });
          }
          return { width: de.clientWidth, scrollWidth: de.scrollWidth, overflow: over, offenders: offenders.slice(0, 8), title: document.title, h1: document.querySelector('h1')?.textContent.trim().slice(0,80) };
        })()`, returnByValue: true
      });
      console.log(JSON.stringify({ page: p.name, width: w, ...layout.result.value }, null, 0));
      if (process.env.RECTS) {
        const sels = JSON.parse(process.env.RECTS);
        const rects = await send('Runtime.evaluate', {
          expression: `(${JSON.stringify(sels)}).map(s => {
            const el = document.querySelector(s);
            if (!el) return [s, null];
            const r = el.getBoundingClientRect();
            const cs = getComputedStyle(el);
            return [s, { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height), right: Math.round(r.right), overflowX: cs.overflowX, whiteSpace: cs.whiteSpace, fontSize: cs.fontSize, textOverflow: cs.textOverflow }];
          })`, returnByValue: true
        });
        console.log('RECTS: ' + JSON.stringify(rects.result.value));
      }
      if (process.env.FOCUS === '1') {
        const snap = async (label) => {
          const f = await send('Runtime.evaluate', {
            expression: `(() => {
              const el = document.activeElement;
              if (!el || el === document.body) return { label: '${label}', tag: 'BODY' };
              const cs = getComputedStyle(el);
              return { label: '${label}', tag: el.tagName, text: (el.textContent || '').trim().slice(0,40), cls: typeof el.className === 'string' ? el.className.slice(0,50) : '', outline: cs.outlineStyle + ' ' + cs.outlineColor, transform: cs.transform, top: Math.round(el.getBoundingClientRect().top) };
            })()`, returnByValue: true
          });
          console.log('FOCUS: ' + JSON.stringify(f.result.value));
        };
        await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9, nativeVirtualKeyCode: 9 });
        await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9, nativeVirtualKeyCode: 9 });
        await wait(250);
        await snap('first-tab');
        await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9, nativeVirtualKeyCode: 9 });
        await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9, nativeVirtualKeyCode: 9 });
        await wait(250);
        await snap('second-tab');
      }
      if (process.env.A11Y === '1') {
        const a11y = await send('Runtime.evaluate', {
          expression: `(() => {
            const imgs = [...document.images];
            const missingAlt = imgs.filter(i => !i.hasAttribute('alt')).map(i => i.currentSrc.split('/').pop());
            const emptyAlt = imgs.filter(i => i.hasAttribute('alt') && i.getAttribute('alt') === '' && !i.closest('[aria-hidden="true"]') && !i.classList.contains('decorative')).length;
            const lazy = imgs.filter(i => i.loading === 'lazy').length;
            const animating = [...document.querySelectorAll('body *')].filter(el => {
              const cs = getComputedStyle(el);
              return el.getAnimations && el.getAnimations().some(a => a.playState === 'running');
            }).map(el => el.className && typeof el.className === 'string' ? el.className.split(' ')[0] : el.tagName).slice(0, 10);
            const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
            const skip = document.querySelector('.skip-link, a[href="#main-content"]');
            const firstNav = document.querySelector('.main-nav a, .site-nav a, header nav a');
            let focusStyle = null;
            if (firstNav) { firstNav.focus(); const cs = getComputedStyle(firstNav); focusStyle = { outlineStyle: cs.outlineStyle, outlineWidth: cs.outlineWidth, boxShadow: cs.boxShadow !== 'none' }; firstNav.blur(); }
            return { images: imgs.length, missingAlt, emptyAlt, lazy, reduce, runningAnimations: animating.length, runningSample: animating, skipLink: !!skip, focusStyle };
          })()`, returnByValue: true
        });
        console.log('A11Y: ' + JSON.stringify(a11y.result.value));
      }
      const full = process.env.FULL === '1';
      let shot;
      if (full) {
        const metrics = await send('Page.getLayoutMetrics');
        const height = Math.min(Math.ceil(metrics.cssContentSize.height), 12000);
        shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: w, height, scale: 1 } });
        fs.writeFileSync(path.join(OUT, `${p.name}_${w}_full.png`), Buffer.from(shot.data, 'base64'));
      } else {
        shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
        fs.writeFileSync(path.join(OUT, `${p.name}_${w}.png`), Buffer.from(shot.data, 'base64'));
      }
    }
  }
  if (consoleErrors.length) console.log('CONSOLE_ERRORS:\n' + [...new Set(consoleErrors)].join('\n'));
  else console.log('CONSOLE_ERRORS: none');
  ws.close();
  chrome.kill();
  try { fs.rmSync(tempDir, { recursive: true, force: true }); } catch (e) {}
}
main().catch(e => { console.error(e); process.exit(1); });
