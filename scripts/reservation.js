document.addEventListener("DOMContentLoaded", () => {
  // Form Validation
  const eventForm = document.getElementById("event-form");

  if (eventForm) {
    eventForm.addEventListener("submit", function (e) {
      e.preventDefault();

      // Basic validation
      const name = document.getElementById("name").value;
      const email = document.getElementById("email").value;
      const phone = document.getElementById("phone").value;
      const eventType = document.getElementById("event-type").value;
      const eventDate = document.getElementById("event-date").value;
      const guests = document.getElementById("guests").value;
      const locationType = document.querySelector(
        'input[name="location_type"]:checked'
      );

      if (name.trim() === "") {
        alert("Please enter your name");
        return;
      }

      if (!validateEmail(email)) {
        alert("Please enter a valid email address");
        return;
      }

      if (!validatePhone(phone)) {
        alert("Please enter a valid phone number");
        return;
      }

      if (eventType === "") {
        alert("Please select an event type");
        return;
      }

      if (eventDate === "") {
        alert("Please select an event date");
        return;
      }

      // Check if selected date is in the future
      const selectedDate = new Date(eventDate);
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      if (selectedDate < today) {
        alert("Please select a future date for your event");
        return;
      }

      if (guests === "" || guests < 1) {
        alert("Please enter a valid number of guests");
        return;
      }

      if (!locationType) {
        alert("Please select a location type");
        return;
      }

      // If all validations pass, submit the form
      this.submit();
    });
  }

  // Dynamic form behavior based on event type
  const eventTypeSelect = document.getElementById("event-type");

  if (eventTypeSelect) {
    eventTypeSelect.addEventListener("change", function () {
      const selectedValue = this.value;
      const accessoriesCheckboxes = document.querySelectorAll(
        'input[name="accessories[]"]'
      );

      // Reset all checkboxes
      accessoriesCheckboxes.forEach((checkbox) => {
        checkbox.parentElement.style.display = "flex";
      });

      // Show/hide specific accessories based on event type
      if (selectedValue === "wedding") {
        // Show wedding-specific options
      } else if (selectedValue === "birthday") {
        // Show birthday-specific options
      } else if (selectedValue === "corporate") {
        // Show corporate-specific options
      }
    });
  }

  // Date picker minimum date (today)
  const eventDateInput = document.getElementById("event-date");

  if (eventDateInput) {
    const today = new Date();
    const yyyy = today.getFullYear();
    let mm = today.getMonth() + 1;
    let dd = today.getDate();

    if (mm < 10) mm = "0" + mm;
    if (dd < 10) dd = "0" + dd;

    const formattedToday = yyyy + "-" + mm + "-" + dd;
    eventDateInput.setAttribute("min", formattedToday);
  }

  // Helper Functions
  function validateEmail(email) {
    const re =
      /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
  }

  function validatePhone(phone) {
    // const re = /^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/;
    const re =
      /^(?:\+961|961)?(1|0?3[0-9]?|[4-6]|70|71|76|78|79|7|81?|9)\d{6}$/;

    return re.test(String(phone));
  }
});
