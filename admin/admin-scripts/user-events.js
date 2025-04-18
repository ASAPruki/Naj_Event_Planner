// Toggle sidebar on mobile
const toggleSidebar = document.getElementById("toggleSidebar");
const adminSidebar = document.getElementById("adminSidebar");
const adminMain = document.getElementById("adminMain");

if (toggleSidebar) {
  toggleSidebar.addEventListener("click", function () {
    adminSidebar.classList.toggle("active");
    adminMain.classList.toggle("sidebar-active");
  });
}

// Close sidebar when clicking outside on mobile
document.addEventListener("click", function (event) {
  const isClickInsideSidebar = adminSidebar.contains(event.target);
  const isClickInsideToggle = toggleSidebar.contains(event.target);

  if (
    window.innerWidth <= 992 &&
    !isClickInsideSidebar &&
    !isClickInsideToggle &&
    adminSidebar.classList.contains("active")
  ) {
    adminSidebar.classList.remove("active");
    adminMain.classList.remove("sidebar-active");
  }
});
