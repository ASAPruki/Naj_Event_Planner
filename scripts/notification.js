document.addEventListener("DOMContentLoaded", () => {
  // Elements
  const notificationBell = document.getElementById("notificationBell");
  const notificationSidebar = document.getElementById("notificationSidebar");
  const closeNotifications = document.getElementById("closeNotifications");
  const notificationOverlay = document.getElementById("notificationOverlay");
  const markAllReadBtn = document.getElementById("markAllRead");
  const notificationItems = document.querySelectorAll(".notification-item");

  // Toggle notification sidebar
  if (notificationBell) {
    notificationBell.addEventListener("click", () => {
      notificationSidebar.classList.add("active");
      notificationOverlay.classList.add("active");
      document.body.style.overflow = "hidden";
    });
  }

  // Close notification sidebar
  if (closeNotifications) {
    closeNotifications.addEventListener("click", closeNotificationSidebar);
  }

  // Close when clicking on overlay
  if (notificationOverlay) {
    notificationOverlay.addEventListener("click", closeNotificationSidebar);
  }

  // Mark all notifications as read
  if (markAllReadBtn) {
    markAllReadBtn.addEventListener("click", () => {
      // Send AJAX request to mark all as read
      fetch("../APIs/mark-all-read.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Update UI
            document
              .querySelectorAll(".notification-item.unread")
              .forEach((item) => {
                item.classList.remove("unread");
                item.querySelector(".notification-status")?.remove();
              });

            // Remove notification badge
            const badge = document.querySelector(".notification-badge");
            if (badge) {
              badge.remove();
            }
          }
        })
        .catch((error) => {
          console.error("Error marking notifications as read:", error);
        });
    });
  }

  // Mark individual notification as read when clicked
  notificationItems.forEach((item) => {
    item.addEventListener("click", function () {
      const notificationId = this.dataset.id;

      // Send AJAX request to mark as read
      fetch("../APIs/mark-notification-read.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ id: notificationId }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Update UI
            this.classList.remove("unread");
            const statusDot = this.querySelector(".notification-status");
            if (statusDot) {
              statusDot.remove();
            }

            // Update badge count
            const badge = document.querySelector(".notification-badge");
            if (badge) {
              const currentCount = Number.parseInt(badge.textContent);
              if (currentCount > 1) {
                badge.textContent = currentCount - 1;
              } else {
                badge.remove();
              }
            }
          }
        })
        .catch((error) => {
          console.error("Error marking notification as read:", error);
        });
    });
  });

  // Function to close notification sidebar
  function closeNotificationSidebar() {
    notificationSidebar.classList.remove("active");
    notificationOverlay.classList.remove("active");
    document.body.style.overflow = "";
  }
});
