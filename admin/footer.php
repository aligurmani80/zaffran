    </main>
  </div>
</div>

<script>
// Mobile Drawer Toggle
const mobileBtn = document.getElementById('mobileMenuBtn');
const sidebar = document.getElementById('adminSidebar');
const backdrop = document.getElementById('sidebarBackdrop');

if (mobileBtn && sidebar && backdrop) {
  mobileBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('active');
  });

  backdrop.addEventListener('click', () => {
    sidebar.classList.remove('open');
    backdrop.classList.remove('active');
  });
}
</script>
</body>
</html>
