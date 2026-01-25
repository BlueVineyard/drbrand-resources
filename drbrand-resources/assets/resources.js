(() => {
  const initCustomSelects = () => {
    const selects = document.querySelectorAll(".dbr-custom-select");

    selects.forEach((select) => {
      const trigger = select.querySelector(".dbr-custom-select__trigger");
      const valueDisplay = select.querySelector(".dbr-custom-select__value");
      const options = select.querySelectorAll(".dbr-custom-select__option");
      const hiddenSelect = select.querySelector(".dbr-custom-select__hidden");

      // Toggle dropdown
      trigger.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();

        // Close other open selects
        document.querySelectorAll(".dbr-custom-select.is-open").forEach((s) => {
          if (s !== select) {
            s.classList.remove("is-open");
          }
        });

        select.classList.toggle("is-open");
      });

      // Handle option selection
      options.forEach((option) => {
        option.addEventListener("click", (e) => {
          e.stopPropagation();

          const value = option.dataset.value;
          const text = option.textContent.trim();

          // Update display
          valueDisplay.textContent = text;

          // Update hidden select
          hiddenSelect.value = value;

          // Update selected state
          options.forEach((opt) => opt.classList.remove("is-selected"));
          option.classList.add("is-selected");

          // Close dropdown
          select.classList.remove("is-open");
        });
      });
    });

    // Close dropdowns when clicking outside
    document.addEventListener("click", () => {
      document.querySelectorAll(".dbr-custom-select.is-open").forEach((s) => {
        s.classList.remove("is-open");
      });
    });

    // Close dropdowns on Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        document.querySelectorAll(".dbr-custom-select.is-open").forEach((s) => {
          s.classList.remove("is-open");
        });
      }
    });
  };

  const initExcerptToggles = () => {
    const wrappers = document.querySelectorAll(".dbr-card__excerpt-wrapper");

    wrappers.forEach((wrapper) => {
      const excerpt = wrapper.querySelector(".dbr-card__excerpt");
      const button = wrapper.querySelector(".dbr-card__read-more");

      if (!excerpt || !button) return;

      // Check if text overflows (more than 3 lines)
      const lineHeight = parseFloat(getComputedStyle(excerpt).lineHeight);
      const maxHeight = lineHeight * 3;

      // Temporarily expand to measure full height
      excerpt.classList.add("is-expanded");
      const fullHeight = excerpt.scrollHeight;
      excerpt.classList.remove("is-expanded");

      if (fullHeight > maxHeight + 1) {
        wrapper.classList.add("has-overflow");

        button.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();

          const isExpanded = excerpt.classList.toggle("is-expanded");
          button.textContent = isExpanded ? "Show less" : "Read more";
        });
      }
    });
  };

  const init = () => {
    // Initialize custom dropdowns
    initCustomSelects();

    // Initialize excerpt read more toggles
    initExcerptToggles();

    // Initialize video cards
    const cards = document.querySelectorAll(".dbr-card--video");
    if (!cards.length) {
      return;
    }

    let modal = document.querySelector(".dbr-modal");
    if (!modal) {
      modal = document.createElement("div");
      modal.className = "dbr-modal";
      modal.innerHTML = `
				<div class="dbr-modal__content">
					<button class="dbr-modal__close" type="button" aria-label="Close">×</button>
					<div class="dbr-modal__video"></div>
				</div>
			`;
      document.body.appendChild(modal);
    }

    const videoContainer = modal.querySelector(".dbr-modal__video");
    const closeButton = modal.querySelector(".dbr-modal__close");

    const openModal = (videoUrl) => {
      if (!videoUrl) {
        return;
      }

      videoContainer.innerHTML = buildVideoMarkup(videoUrl);
      modal.classList.add("is-active");

      if (window.gsap) {
        gsap.fromTo(
          ".dbr-modal__content",
          { scale: 0.9, opacity: 0 },
          { scale: 1, opacity: 1, duration: 0.35, ease: "power2.out" }
        );
      }
    };

    const closeModal = () => {
      if (window.gsap) {
        gsap.to(".dbr-modal__content", {
          scale: 0.9,
          opacity: 0,
          duration: 0.25,
          ease: "power2.in",
          onComplete: () => {
            modal.classList.remove("is-active");
            videoContainer.innerHTML = "";
          },
        });
        return;
      }

      modal.classList.remove("is-active");
      videoContainer.innerHTML = "";
    };

    const buildVideoMarkup = (url) => {
      const lower = url.toLowerCase();
      if (lower.includes("youtube.com") || lower.includes("youtu.be")) {
        const idMatch = url.match(
          /(?:youtu\.be\/|v=|embed\/)([A-Za-z0-9_-]{6,})/
        );
        const id = idMatch ? idMatch[1] : "";
        return id
          ? `<iframe src="https://www.youtube.com/embed/${id}?autoplay=1" allow="autoplay; fullscreen" allowfullscreen></iframe>`
          : "";
      }
      if (lower.includes("vimeo.com")) {
        const idMatch = url.match(/vimeo\.com\/(\d+)/);
        const id = idMatch ? idMatch[1] : "";
        return id
          ? `<iframe src="https://player.vimeo.com/video/${id}?autoplay=1" allow="autoplay; fullscreen" allowfullscreen></iframe>`
          : "";
      }
      if (
        lower.endsWith(".mp4") ||
        lower.endsWith(".webm") ||
        lower.endsWith(".ogg")
      ) {
        return `<video controls autoplay><source src="${url}"></video>`;
      }
      return `<iframe src="${url}" allow="autoplay; fullscreen" allowfullscreen></iframe>`;
    };

    cards.forEach((card) => {
      const button = card.querySelector(".dbr-card__play");
      const videoUrl = card.dataset.videoUrl;
      if (button) {
        button.addEventListener("click", (event) => {
          event.preventDefault();
          openModal(videoUrl);
        });
      }
    });

    closeButton.addEventListener("click", closeModal);
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
