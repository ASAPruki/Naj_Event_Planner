document.addEventListener("DOMContentLoaded", function () {
  // Toggle sidebar on mobile
  const toggleSidebar = document.getElementById("toggleSidebar");
  const adminSidebar = document.getElementById("adminSidebar");
  const adminMain = document.getElementById("adminMain");

  if (toggleSidebar) {
    toggleSidebar.addEventListener("click", function (event) {
      event.stopPropagation(); // Prevent click from bubbling up
      adminSidebar.classList.toggle("active");
      adminMain.classList.toggle("sidebar-active");
    });
  }

  // Close sidebar when clicking outside
  document.addEventListener("click", function (event) {
    const isClickInsideSidebar = adminSidebar.contains(event.target);
    const isClickOnToggle = toggleSidebar.contains(event.target);

    if (
      !isClickInsideSidebar &&
      !isClickOnToggle &&
      adminSidebar.classList.contains("active")
    ) {
      adminSidebar.classList.remove("active");
      adminMain.classList.remove("sidebar-active");
    }
  });

  // Toggle image upload form
  const showUploadForm = document.getElementById("showUploadForm");
  const imageUploadForm = document.getElementById("imageUploadForm");
  const cancelUpload = document.getElementById("cancelUpload");

  if (showUploadForm && imageUploadForm) {
    showUploadForm.addEventListener("click", function () {
      imageUploadForm.style.display = "block";
      showUploadForm.style.display = "none";
    });
  }

  if (cancelUpload && imageUploadForm && showUploadForm) {
    cancelUpload.addEventListener("click", function () {
      imageUploadForm.style.display = "none";
      showUploadForm.style.display = "inline-block";
    });
  }
});
