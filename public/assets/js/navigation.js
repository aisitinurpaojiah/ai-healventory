document.addEventListener("DOMContentLoaded", function () {
  console.log("🚀 Navigation script loaded");

  // ===================================
  // 1. SIDEBAR NAVIGATION
  // ===================================
  const menuItems = document.querySelectorAll(".sidebar .menu li[data-page]");

  menuItems.forEach((item) => {
    item.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const page = this.getAttribute("data-page");
      if (page) {
        console.log("📍 Navigating to:", page);
        window.location.href = page;
      }
    });
  });

  // ===================================
  // 2. LOGOUT FUNCTIONALITY
  // ===================================
  const btnLogout = document.getElementById("btnLogout");
  const logoutModal = document.getElementById("logoutModal");
  const confirmLogout = document.getElementById("confirmLogout");
  const cancelLogout = document.getElementById("cancelLogout");

  // Open logout modal
  if (btnLogout && logoutModal) {
    btnLogout.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      console.log("🚪 Opening logout modal");

      logoutModal.classList.add("active");
      document.body.style.overflow = "hidden";
    });
  }

  // Cancel logout
  if (cancelLogout && logoutModal) {
    cancelLogout.addEventListener("click", function (e) {
      e.preventDefault();
      console.log("❌ Logout cancelled");

      logoutModal.classList.remove("active");
      document.body.style.overflow = "auto";
    });
  }

  // Confirm logout - FIXED PATH
  if (confirmLogout) {
    confirmLogout.addEventListener("click", function (e) {
      e.preventDefault();
      console.log("✅ Logout confirmed");

      // Determine logout path based on current directory
      const currentPath = window.location.pathname;
      let logoutPath = "../logout.php";

      if (currentPath.includes("/admin/")) {
        logoutPath = "../logout.php";
      } else if (currentPath.includes("/manager/")) {
        logoutPath = "../logout.php";
      } else if (currentPath.includes("/staff/")) {
        logoutPath = "../logout.php";
      }

      window.location.href = logoutPath;
    });
  }

  // Close modal when clicking outside
  if (logoutModal) {
    logoutModal.addEventListener("click", function (e) {
      if (e.target === logoutModal) {
        logoutModal.classList.remove("active");
        document.body.style.overflow = "auto";
      }
    });
  }

  // ===================================
  // 3. ESC KEY TO CLOSE MODALS
  // ===================================
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      const activeModals = document.querySelectorAll(
        ".modal.active, .logout-modal.active, .modal-blur.active"
      );
      activeModals.forEach((modal) => {
        modal.classList.remove("active");
        document.body.style.overflow = "auto";
      });
    }
  });

  // ===================================
  // 4. DEBUG INFO
  // ===================================
  console.log("✅ Navigation initialized");
  console.log("📊 Menu items found:", menuItems.length);
  console.log("🔐 Logout button:", btnLogout ? "Found" : "NOT FOUND");
  console.log("📦 Logout modal:", logoutModal ? "Found" : "NOT FOUND");
});
