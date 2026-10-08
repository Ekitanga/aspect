(() => {
  "use strict";

  const enhanceGallery = (gallery) => {
    const rail = gallery.querySelector(".flex-control-thumbs");
    if (!rail || rail.dataset.aspectEnhanced === "true") {
      return;
    }

    rail.dataset.aspectEnhanced = "true";
    rail.setAttribute("aria-label", "Product image thumbnails");

    const updateState = () => {
      const thumbnails = [...rail.querySelectorAll("img")];
      rail.dataset.imageCount = String(thumbnails.length);

      thumbnails.forEach((thumbnail, index) => {
        const isActive = thumbnail.classList.contains("flex-active");
        const item = thumbnail.closest("li");

        thumbnail.tabIndex = 0;
        thumbnail.setAttribute("role", "button");
        thumbnail.setAttribute(
          "aria-label",
          `View product image ${index + 1} of ${thumbnails.length}`
        );
        thumbnail.setAttribute("aria-current", isActive ? "true" : "false");
        item?.classList.toggle("is-active", isActive);

        if (thumbnail.dataset.aspectKeyboardReady !== "true") {
          thumbnail.dataset.aspectKeyboardReady = "true";
          thumbnail.addEventListener("keydown", (event) => {
            if (event.key !== "Enter" && event.key !== " ") {
              return;
            }
            event.preventDefault();
            thumbnail.click();
          });
        }
      });
    };

    const observer = new MutationObserver((mutations) => {
      const needsUpdate = mutations.some(
        (mutation) =>
          mutation.type === "childList" ||
          (mutation.type === "attributes" && mutation.target instanceof HTMLImageElement)
      );
      if (needsUpdate) {
        updateState();
      }
    });

    observer.observe(rail, {
      subtree: true,
      childList: true,
      attributes: true,
      attributeFilter: ["class"],
    });

    rail.addEventListener("click", () => requestAnimationFrame(updateState));
    updateState();
  };

  const initialize = () => {
    document
      .querySelectorAll(".woocommerce-product-gallery")
      .forEach(enhanceGallery);
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }

  window.addEventListener("load", initialize, { once: true });
})();
