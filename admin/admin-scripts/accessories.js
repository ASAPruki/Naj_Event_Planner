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

// Toggle add accessory form
const toggleAddForm = document.getElementById("toggleAddForm");
const addAccessoryForm = document.getElementById("addAccessoryForm");

if (toggleAddForm && addAccessoryForm) {
  toggleAddForm.addEventListener("click", function () {
    if (addAccessoryForm.style.display === "none") {
      addAccessoryForm.style.display = "block";
      toggleAddForm.innerHTML = '<i class="fas fa-minus"></i> Hide Form';
    } else {
      addAccessoryForm.style.display = "none";
      toggleAddForm.innerHTML = '<i class="fas fa-plus"></i> Show Form';
    }
  });
}

// Delete confirmation modal
const deleteButtons = document.querySelectorAll(".delete-accessory");
const deleteModal = document.getElementById("deleteModal");
const deleteAccessoryName = document.getElementById("deleteAccessoryName");
const deleteAccessoryId = document.getElementById("deleteAccessoryId");
const cancelDelete = document.getElementById("cancelDelete");

deleteButtons.forEach((button) => {
  button.addEventListener("click", function () {
    const id = this.getAttribute("data-id");
    const name = this.getAttribute("data-name");

    deleteAccessoryId.value = id;
    deleteAccessoryName.textContent = name;
    deleteModal.style.display = "block";
  });
});

if (cancelDelete) {
  cancelDelete.addEventListener("click", function () {
    deleteModal.style.display = "none";
  });
}

// Close modal when clicking outside
window.addEventListener("click", function (event) {
  if (event.target === deleteModal) {
    deleteModal.style.display = "none";
  }
});
