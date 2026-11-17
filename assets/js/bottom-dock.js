(function () {
  function initBottomDock() {
    const wrapper = document.getElementById("kb-bottom-dock");
    const toggleBtn = document.getElementById("kb-dock-toggle");
    const panel = document.getElementById("kb-dock-panel");

    if (!wrapper || !toggleBtn || !panel) return;
    if (toggleBtn.dataset.kbBound === "true") return;
    toggleBtn.dataset.kbBound = "true";

    const labelEl = toggleBtn.querySelector(".kb-toggle-label");
    const iconEl = toggleBtn.querySelector(".kb-toggle-icon");

    function setMenuState(isOpen) {
      panel.classList.toggle("is-open", isOpen);
      toggleBtn.setAttribute("aria-expanded", String(isOpen));
      panel.setAttribute("aria-hidden", String(!isOpen));

      if (labelEl && iconEl) {
        labelEl.textContent = isOpen ? "CLOSE" : "MENU";
        iconEl.textContent = isOpen ? "✕" : "✦";
      }
    }

    toggleBtn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const currentlyOpen = panel.classList.contains("is-open");
      setMenuState(!currentlyOpen);
    });

    // Close when clicking anywhere outside the dock
    document.addEventListener("click", function (e) {
      if (!wrapper.contains(e.target)) {
        setMenuState(false);
      }
    });

    // Close on Escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        setMenuState(false);
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initBottomDock);
  } else {
    initBottomDock();
  }
})();