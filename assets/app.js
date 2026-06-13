import "./bootstrap";
// assets/app.js
import "./styles/app.scss";
console.log("JS chargé !");

function initBurgerMenu() {
  const burger = document.getElementById("burger");
  const navMenu = document.querySelector(".nav-menu");

  if (!burger || !navMenu) return; // Vérification

  // Réinitialisation menu fermé SANS transition
  navMenu.style.transition = "none";
  burger.classList.remove("active");
  navMenu.classList.remove("active");
  navMenu.offsetHeight; // Force reflow
  navMenu.style.transition = "";

  burger.addEventListener("click", () => {
    burger.classList.toggle("active");
    navMenu.classList.toggle("active");
  });

  navMenu.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      burger.classList.remove("active");
      navMenu.classList.remove("active");
    });
  });
}

document.addEventListener("turbo:load", initBurgerMenu);

// intialise les avis ///////////////////////////////////////////////

function initCommentForm() {
  const commentForm = document.querySelector("#comment_form");
  if (!commentForm) return;

  // Eviter d'ajouter plusieurs handlers
  if (commentForm.dataset.bound === "1") return;
  commentForm.dataset.bound = "1";

  commentForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(commentForm);
    const action = commentForm.getAttribute("action");

    fetch(action, {
      method: "POST",
      body: formData,
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          // afficher message
          const msg = document.getElementById("comment_success");
          if (msg) msg.textContent = data.message;

          // remplacer uniquement la partie list
          const commentsList = document.getElementById("comments_list");
          if (commentsList && data.html) {
            commentsList.innerHTML = data.html;
          }

          commentForm.reset();
        } else {
          alert(data.message || "Erreur lors de l'envoi du commentaire.");
        }
      })
      .catch((err) => {
        console.error(err);
        alert("Erreur lors de l'envoi du commentaire.");
      });
  });
}

// LIGHTBOX ///////////////////////////////////////////////

let lightboxInitialized = false; // Variable globale

function initializeLightbox() {
  if (lightboxInitialized) return; // Sort si déjà initialisée

  const galleryItems = document.querySelectorAll(".gallery-item");
  const lightbox = document.getElementById("lightbox");
  const lightboxImage = document.getElementById("lightbox-image");
  const closeButton = document.querySelector(".my-close");
  const prevButton = document.querySelector(".prev");
  const nextButton = document.querySelector(".next");
  const zoomIcon = document.querySelector(".zoom-icon");
  const fullscreenIcon = document.querySelector(".fullscreen-icon");
  const infoIcon = document.querySelector(".info-icon");
  const infoPopup = document.getElementById("info-popup");
  const lightboxTitle = document.getElementById("lightbox-title");

  if (!lightbox || !lightboxImage) return; // Pas de lightbox sur cette page

  const closeInfoBtPopup = infoPopup;
  const lightboxImages = document.querySelectorAll(".lightbox-image");

  lightboxImages.forEach((img) => {
    img.addEventListener("contextmenu", (e) => e.preventDefault());
  });

  let currentIndex = 0;
  let isTransitioning = false;

  function showLightbox(index, direction = null) {
    if (isTransitioning) return;
    isTransitioning = true;

    const item = galleryItems[index];
    const newSrc = item.getAttribute("href");

    const title = item.dataset.title || "Image sans titre";
    let ariaLabel = item.dataset.ariaLabel || item.getAttribute("aria-label");

    // fallback : si ariaLabel vide, on prend le title
    if (!ariaLabel || ariaLabel.trim() === "") {
      ariaLabel = title;
    }

    const onTransitionEnd = () => {
      lightboxImage.removeEventListener("transitionend", onTransitionEnd);
      isTransitioning = false;
    };

    if (direction) {
      const exitDirection = direction === "next" ? "-100%" : "100%";
      lightboxImage.style.transition = "transform 0.5s ease, opacity 0.5s ease";
      lightboxImage.style.transform = `translateX(${exitDirection})`;
      lightboxImage.style.opacity = "0";

      setTimeout(() => {
        lightboxImage.style.transition = "none";
        lightboxImage.style.transform = `translateX(${
          direction === "next" ? "100%" : "-100%"
        })`;
        lightboxImage.src = newSrc;
        lightboxImage.alt = ariaLabel;
        lightbox.setAttribute("aria-label", ariaLabel);

        requestAnimationFrame(() => {
          lightboxImage.style.transition =
            "transform 0.5s ease, opacity 0.5s ease";
          lightboxImage.style.transform = "translateX(0)";
          lightboxImage.style.opacity = "1";
          lightboxImage.addEventListener("transitionend", onTransitionEnd, {
            once: true,
          });
        });
      }, 500);
    } else {
      lightboxImage.src = newSrc;
      lightboxImage.alt = ariaLabel;
      lightboxImage.setAttribute("aria-label", ariaLabel);
    }

    if (lightboxTitle) lightboxTitle.textContent = title;

    lightbox.style.display = "flex";
    setTimeout(() => {
      lightbox.style.opacity = "1";
      isTransitioning = false;
    }, 500);

    currentIndex = index;

    // =======================
    // WIDGET COMMENTAIRES
    // =======================
    const tableauId = item.dataset.id;
    const commentContainer = document.getElementById("comments_container");

    if (tableauId && commentContainer) {
      fetch(`/tableau/${tableauId}/avis`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
        .then((res) => res.json())
        .then((json) => {
          if (json.success && json.html) {
            // injecte le HTML du widget
            commentContainer.innerHTML = json.html;

            // --- Initialisation du widget ---
            const widget = commentContainer.querySelector(".comment-widget");
            if (widget) {
              const content = widget.querySelector(".comment-content");
              const closeBtn = widget.querySelector(".close-btn");
              const header = widget.querySelector(".comment-header");

              // contenu initial replié
              // état initial
              widget.style.transform = "translateY(0)";
              widget.style.transition = "transform 0.3s ease";

              content.style.opacity = "0";
              content.style.transition = "opacity 0.3s ease";
              content.style.pointerEvents = "none";

              // au survol du header → affiche le contenu
              if (header) {
                header.addEventListener("click", () => {
                  widget.style.transform = "translateY(-500px)";
                  content.style.opacity = "1";
                  content.style.pointerEvents = "auto";
                  closeBtn.style.opacity = "1"; // <- fait apparaître la croix
                });
              }

              // croix → replie le contenu
              if (closeBtn) {
                closeBtn.addEventListener("click", (e) => {
                  e.stopPropagation();
                  widget.style.transform = "translateY(0)";
                  content.style.opacity = "0";
                  content.style.pointerEvents = "none";
                  closeBtn.style.opacity = "0"; // <- cache à nouveau la croix
                });
              }
            }

            // --- Formulaire Ajax ---
            if (typeof initCommentForm === "function") {
              initCommentForm();
            }
          } else {
            commentContainer.innerHTML =
              "<p>Impossible de charger les commentaires.</p>";
          }
        })
        .catch((err) => {
          console.error("Erreur chargement widget commentaires :", err);
          commentContainer.innerHTML =
            "<p>Erreur lors du chargement des commentaires.</p>";
        });
    }
  }

  function closeLightbox() {
    if (infoPopup && infoPopup.style.display == "block") hidePopup();

    if (document.fullscreenElement) {
      document.exitFullscreen().finally(() => closeLightboxContent());
    } else {
      closeLightboxContent();
    }
  }

  function closeLightboxContent() {
    if (lightbox) {
      lightbox.style.opacity = "0";
      setTimeout(() => (lightbox.style.display = "none"), 500);
    }
  }

  function showPopup() {
    if (!infoPopup) return;
    const item = galleryItems[currentIndex];
    if (!item) return;

    const {
      title = "Image sans titre",
      category = "",
      date = "",
      dimension = "",
      commentaires = "",
      forsale = "",
      description = "",
      keywords = "",
    } = item.dataset;

    [
      "popup-title",
      "popup-category",
      "popup-date",
      "popup-dimension",
      "popup-description",
      "popup-commentaires",
      "popup-keywords",
      "popup-forsale",
    ].forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        switch (id) {
          case "popup-title":
            el.textContent = title;
            break;
          case "popup-category":
            el.querySelector(".value") &&
              (el.querySelector(".value").textContent = category);
            break;
          case "popup-date":
            el.querySelector(".value") &&
              (el.querySelector(".value").textContent = date);
            break;
          case "popup-dimension":
            el.querySelector(".value") &&
              (el.querySelector(".value").textContent = dimension);
            break;
          case "popup-description":
            el.querySelector(".value") &&
              (el.querySelector(".value").textContent = description);
            break;
          case "popup-commentaires":
            el.querySelector(".value") &&
              (el.querySelector(".value").textContent = commentaires);
            break;
          case "popup-keywords":
            el.querySelector(".value") &&
              (el.querySelector(".value").textContent = keywords);
            break;
          case "popup-forsale":
            el.querySelector(".value") &&
              (el.querySelector(".value").textContent = forsale);
            break;
        }
      }
    });

    infoPopup.style.display = "block";
    setTimeout(() => {
      if (lightbox) lightbox.style.background = "rgba(0,0,0,0.9)";
    }, 10);
    setTimeout(() => {
      infoPopup.style.opacity = 1;
    }, 10);
  }

  function hidePopup() {
    if (!infoPopup) return;
    infoPopup.style.opacity = 0;
    if (lightbox) lightbox.style.background = "rgba(0,0,0,0.8)";
    setTimeout(() => {
      infoPopup.style.display = "none";
    }, 300);
  }

  function slideTransition(direction) {
    if (infoPopup && infoPopup.style.display == "block") hidePopup();
    const newIndex =
      direction === "next"
        ? (currentIndex + 1) % galleryItems.length
        : (currentIndex - 1 + galleryItems.length) % galleryItems.length;
    setTimeout(() => showLightbox(newIndex, direction), 510);
  }

  galleryItems.forEach((item, index) => {
    item.addEventListener("click", (e) => {
      e.preventDefault();
      showLightbox(index);
    });
  });

  if (closeButton)
    closeButton.onclick = () => {
      if (lightboxImage && !lightboxImage.classList.contains("zoomed"))
        closeLightbox();
    };
  if (closeInfoBtPopup)
    closeInfoBtPopup.onclick = () => {
      if (lightboxImage && !lightboxImage.classList.contains("zoomed"))
        hidePopup();
    };
  if (prevButton)
    prevButton.onclick = () => {
      if (lightboxImage && !lightboxImage.classList.contains("zoomed"))
        slideTransition("prev");
    };
  if (nextButton)
    nextButton.onclick = () => {
      if (lightboxImage && !lightboxImage.classList.contains("zoomed"))
        slideTransition("next");
    };

  if (zoomIcon) {
    zoomIcon.onclick = () => {
      if (!lightboxImage) return;
      if (infoPopup && infoPopup.style.display === "block") hidePopup();
      const zoomed = lightboxImage.classList.toggle("zoomed");
      lightboxImage.style.transform = zoomed ? "scale(3)" : "scale(1)";
      if (zoomed) enableImageDragging();
      else {
        lightboxImage.onmousedown = null;
        lightboxImage.onmousemove = null;
        lightboxImage.onmouseup = null;
        lightboxImage.style.top = "0px";
        lightboxImage.style.left = "0px";
        setIconsDisabled(false);
      }
      setIconsDisabled(zoomed);
    };
  }

  if (infoIcon)
    infoIcon.onclick = () => {
      if (!infoPopup) return;
      infoPopup.style.display === "block" ? hidePopup() : showPopup();
    };
  if (fullscreenIcon)
    fullscreenIcon.onclick = () => {
      if (!lightbox) return;
      if (infoPopup && infoPopup.style.display == "block") hidePopup();
      !document.fullscreenElement
        ? lightbox.requestFullscreen()
        : document.exitFullscreen();
    };

  function enableImageDragging() {
    if (!lightboxImage) return;
    let pos1 = 0,
      pos2 = 0,
      pos3 = 0,
      pos4 = 0,
      isDragging = false;
    lightboxImage.onmousedown = function (e) {
      if (!lightboxImage.classList.contains("zoomed")) return;
      isDragging = true;
      pos3 = e.clientX;
      pos4 = e.clientY;
      document.onmouseup = closeDrag;
      document.onmousemove = elementDrag;
    };
    function elementDrag(e) {
      if (!isDragging) return;
      e.preventDefault();
      pos1 = pos3 - e.clientX;
      pos2 = pos4 - e.clientY;
      pos3 = e.clientX;
      pos4 = e.clientY;
      lightboxImage.style.top = lightboxImage.offsetTop - pos2 + "px";
      lightboxImage.style.left = lightboxImage.offsetLeft - pos1 + "px";
    }
    function closeDrag() {
      isDragging = false;
      document.onmouseup = null;
      document.onmousemove = null;
    }
  }

  function setIconsDisabled(disabled) {
    [
      closeButton,
      prevButton,
      nextButton,
      document.querySelector(".lightbox-nav"),
    ].forEach((icon) => {
      if (icon) icon.classList.toggle("disabled", disabled);
    });
  }

  lightboxInitialized = true; // Marquer comme initialisée
}

document.addEventListener("turbo:load", () => {
  // reset si nécessaire
  lightboxInitialized = false;
  initializeLightbox();
});

// ============================================
// SLIDESHOW
// ============================================

class Slideshow {
  constructor(el) {
    this.DOM = { el: el };
    this.config = {
      slideshow: { delay: 3000, pagination: { duration: 3 } },
    };
    this.init();
  }

  init() {
    if (!this.DOM.el) return;

    const self = this;

    // Charmed titles
    this.DOM.slideTitle = this.DOM.el.querySelectorAll(".slide-title");
    if (this.DOM.slideTitle.length) {
      this.DOM.slideTitle.forEach((title) => {
        charming(title);
        title.classList.add("ready");
      });
    }

    // Swiper 4 initialization
    this.slideshow = new Swiper(this.DOM.el, {
      loop: true,
      autoplay: {
        delay: this.config.slideshow.delay,
        disableOnInteraction: false,
      },
      speed: 500,
      pagination: {
        el: ".slideshow-pagination",
        clickable: true,
        bulletClass: "slideshow-pagination-item",
        bulletActiveClass: "active",
        renderBullet: function (index, className) {
          const number = index < 9 ? "0" + (index + 1) : index + 1;
          let bullet = `<span class="${className}"><span class="pagination-number">${number}</span>`;
          if (index < 9) {
            bullet +=
              '<span class="pagination-separator"><span class="pagination-separator-loader"></span></span>';
          }
          bullet += "</span>";
          return bullet;
        },
      },
      navigation: {
        nextEl: ".slideshow-navigation-button.next",
        prevEl: ".slideshow-navigation-button.prev",
      },
      on: {
        init: function () {
          self.animate("next");
        },
        slideChangeTransitionStart: function () {
          self.animate(this.activeIndex > this.previousIndex ? "next" : "prev");
        },
      },
    });
  }

  animate(direction = "next") {
    const activeSlide = this.DOM.el.querySelector(".swiper-slide-active");
    if (!activeSlide) return;

    const img = activeSlide.querySelector(".slide-image");
    const title = activeSlide.querySelector(".slide-title");
    if (!title) return;

    const letters = Array.from(title.querySelectorAll("span"));
    const lettersOrdered = direction === "next" ? letters : letters.reverse();

    // Animate title letters
    lettersOrdered.forEach((letter, pos) => {
      TweenMax.to(letter, 0.6, {
        ease: Back.easeOut,
        delay: pos * 0.05,
        startAt: { y: "50%", opacity: 0 },
        y: "0%",
        opacity: 1,
      });
    });

    // Animate image
    if (img) {
      TweenMax.to(img, 1.5, {
        ease: Expo.easeOut,
        startAt: { x: direction === "next" ? 200 : -200 },
        x: 0,
      });
    }
  }

  destroy() {
    if (this.slideshow && typeof this.slideshow.destroy === "function") {
      try {
        this.slideshow.destroy(true, true);
      } catch (e) {
        console.warn("Swiper destroy suppressed:", e);
      }
    }
    this.DOM = {};
    this.slideshow = null;
  }
}

// Gestion Turbo & instances
let currentSlideshow = null;

function destroySlideshow() {
  if (currentSlideshow && typeof currentSlideshow.destroy === "function") {
    currentSlideshow.destroy();
    currentSlideshow = null;
  }

  const el = document.querySelector(".slideshow");
  if (el && el.swiper && typeof el.swiper.destroy === "function") {
    try {
      el.swiper.destroy(true, true);
    } catch (e) {
      console.warn("Swiper destroy suppressed:", e);
    }
  }
}

function initSlideshow() {
  const slideshowEl = document.querySelector(".slideshow");
  if (!slideshowEl) {
    destroySlideshow();
    return;
  }

  // Si Swiper n'est pas encore chargé, on attend qu'il le soit
  if (typeof Swiper === "undefined") {
    const script = document.createElement("script");
    script.src =
      "https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.4.1/js/swiper.min.js";
    script.onload = () => {
      destroySlideshow();
      currentSlideshow = new Slideshow(slideshowEl);
    };
    document.body.appendChild(script);
    return;
  }

  destroySlideshow();
  currentSlideshow = new Slideshow(slideshowEl);
}

// Init classique + Turbo
document.addEventListener("turbo:load", initSlideshow);
document.addEventListener("turbo:before-cache", destroySlideshow);

// REMPLIR LES COLONNES DE LA MOSAIQUE GALEERY UNE A UNE ///////////////////////

document.addEventListener("turbo:load", function () {
  const gallery = document.getElementById("gallery");
  if (!gallery) return;

  const columns = gallery.getElementsByClassName("column");
  const items = Array.from(document.querySelectorAll(".gallery-item"));

  // Attendre que toutes les images soient chargées
  const images = items.map((item) => item.querySelector("img"));
  let loadedCount = 0;

  function distributeItems() {
    let index = 0;
    items.forEach((item) => {
      columns[index].appendChild(item);
      index = (index + 1) % columns.length;
    });
  }

  images.forEach((img) => {
    if (img.complete) {
      loadedCount++;
    } else {
      img.addEventListener("load", () => {
        loadedCount++;
        if (loadedCount === images.length) distributeItems();
      });
    }
  });

  if (loadedCount === images.length) distributeItems();
});

document.addEventListener("turbo:load", function () {
  const toggleBtn = document.querySelector(".toggle-filters");
  const filterWrapper = document.querySelector(".filter-wrapper");

  if (!toggleBtn || !filterWrapper) return;

  // Fonction slideUp
  function slideUp(element) {
    element.style.height = element.scrollHeight + "px"; // point de départ
    element.style.opacity = "1";

    requestAnimationFrame(() => {
      element.style.height = "0px";
      element.style.opacity = "0";
    });

    element.addEventListener(
      "transitionend",
      () => {
        element.classList.add("collapsed");
        element.style.height = "0px"; // garder fermé
      },
      { once: true }
    );
  }

  // Fonction slideDown
  function slideDown(element) {
    element.classList.remove("collapsed");
    element.style.height = "0px";
    element.style.opacity = "0";

    requestAnimationFrame(() => {
      element.style.height = element.scrollHeight + "px";
      element.style.opacity = "1";
    });

    element.addEventListener(
      "transitionend",
      () => {
        element.style.height = "auto"; // reset après anim
      },
      { once: true }
    );
  }

  // Restaurer état depuis localStorage
  const isCollapsed = localStorage.getItem("filtersCollapsed") === "true";
  if (isCollapsed) {
    filterWrapper.style.height = "0px";
    filterWrapper.classList.add("collapsed");
    toggleBtn.textContent = "Afficher les filtres";
  } else {
    filterWrapper.style.height = "auto";
    filterWrapper.classList.remove("collapsed");
    toggleBtn.textContent = "Masquer les filtres";
  }

  toggleBtn.addEventListener("click", function () {
    if (filterWrapper.classList.contains("collapsed")) {
      slideDown(filterWrapper);
      toggleBtn.textContent = "Masquer les filtres";
      localStorage.setItem("filtersCollapsed", "false");
    } else {
      slideUp(filterWrapper);
      toggleBtn.textContent = "Afficher les filtres";
      localStorage.setItem("filtersCollapsed", "true");
    }
  });
});

// APERCU IMAGE FORMULAIRE ///////////////////////////

document.addEventListener("turbo:load", function () {
  const fileInput = document.querySelector(
    'input[type="file"][name$="[imageFile]"]'
  );
  const preview = document.getElementById("preview-image");

  if (fileInput) {
    fileInput.addEventListener("change", function (event) {
      const file = event.target.files[0];
      if (file && file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = function (e) {
          preview.src = e.target.result;
          preview.style.display = "block";
        };
        reader.readAsDataURL(file);
      } else {
        preview.style.display = "none";
        preview.src = "#";
      }
    });
  }
});

// Fonction qui retourne le nombre de colonnes selon la largeur
function distributeGallery() {
  const gallery = document.querySelector("#gallery");
  if (!gallery) return;

  const columns = Array.from(gallery.querySelectorAll(".column"));
  const items = Array.from(gallery.querySelectorAll(".gallery-item"));

  // Vide les colonnes
  columns.forEach((col) => (col.innerHTML = ""));

  // Colonne visible seulement
  const visibleColumns = columns.filter((col) => col.offsetParent !== null);
  if (visibleColumns.length === 0) return;

  items.forEach((item, index) => {
    const colIndex = index % visibleColumns.length;
    visibleColumns[colIndex].appendChild(item);
  });
}

function initGallery() {
  // Laisser le CSS s'appliquer avec un petit délai
  setTimeout(distributeGallery, 50);

  window.addEventListener("resize", distributeGallery);
  document.addEventListener("turbo:load", () =>
    setTimeout(distributeGallery, 50)
  );
}

// Initialisation sur page normale
document.addEventListener("DOMContentLoaded", initGallery);

// SLIDERADMIN

document.addEventListener("turbo:load", initAdminSlider);
document.addEventListener("turbo:render", initAdminSlider);
document.addEventListener("turbo:before-cache", () => {
  if (window.sliderAbortController) window.sliderAbortController.abort();
});

function initAdminSlider() {
  const sliderContainer = document.getElementById("slider-items-container");
  const counter = document.getElementById("slider-counter");
  const saveBtn = document.getElementById("save-slider-btn");

  if (!sliderContainer || !counter || !saveBtn) return;

  // Nettoyage d'anciens listeners
  if (window.sliderAbortController) window.sliderAbortController.abort();
  window.sliderAbortController = new AbortController();
  const { signal } = window.sliderAbortController;

  // Fonction pour reconstruire uniquement la preview du slider (images et toggles)
  function updateSliderPreview() {
    sliderContainer.innerHTML = "";
    let sliderCount = 0;

    document.querySelectorAll(".toggle-input").forEach((toggle) => {
      if (toggle.checked) {
        const id = toggle.dataset.id;
        const preview = toggle.dataset.preview || "";
        const titleInput = document.querySelector(
          `.custom-title-input[data-id="${id}"]`
        );
        const title = titleInput ? titleInput.value : "";

        sliderCount++;

        const item = document.createElement("div");
        item.className = "slider-item d-flex flex-column text-center";
        item.dataset.id = id;

        const imgWrapper = document.createElement("div");
        imgWrapper.className = "image-wrapper";

        const img = document.createElement("img");
        img.src = preview;
        img.alt = "Preview";
        img.className = "img-fluid";

        // Toggle en overlay
        const label = document.createElement("label");
        label.className = "toggle-switch overlay-toggle";
        const input = document.createElement("input");
        input.type = "checkbox";
        input.className = "toggle-input-preview";
        input.dataset.id = id;
        input.checked = true;
        input.dataset.preview = preview;
        const span = document.createElement("span");
        span.className = "toggle-slider";
        label.appendChild(input);
        label.appendChild(span);

        imgWrapper.appendChild(img);
        imgWrapper.appendChild(label);

        // Champ titre (non recréé à chaque frappe)
        const customInput = document.createElement("input");
        customInput.type = "text";
        customInput.className = "custom-title-input mt-2";
        customInput.dataset.id = id;
        customInput.value = title;
        customInput.placeholder = "Titre personnalisé";
        customInput.style.zIndex = 10; // assure qu’il est au-dessus du toggle

        item.appendChild(imgWrapper);
        item.appendChild(customInput);
        sliderContainer.appendChild(item);
      }
    });

    counter.textContent = `${sliderCount} / 5 images dans le slider`;
  }

  // Délégation d'événements
  document.addEventListener(
    "change",
    (e) => {
      if (e.target.classList.contains("toggle-input")) {
        updateSliderPreview();
      }
      if (e.target.classList.contains("toggle-input-preview")) {
        const id = e.target.dataset.id;
        const mainToggle = document.querySelector(
          `.toggle-input[data-id="${id}"]`
        );
        if (mainToggle) mainToggle.checked = e.target.checked;
        updateSliderPreview();
      }
    },
    { signal }
  );

  // Pour l’input texte, ne **pas reconstruire le DOM**
  document.addEventListener(
    "input",
    (e) => {
      if (e.target.classList.contains("custom-title-input")) {
        // simple mise à jour du compteur si nécessaire
        const sliderCount = document.querySelectorAll(
          ".toggle-input:checked"
        ).length;
        counter.textContent = `${sliderCount} / 5 images dans le slider`;
      }
    },
    { signal }
  );

  // Sauvegarde AJAX
  saveBtn.addEventListener(
    "click",
    () => {
      const sliderItems = [];
      let sliderCount = 0;

      document.querySelectorAll(".toggle-input").forEach((toggle) => {
        const id = toggle.dataset.id;
        const isInSlider = toggle.checked;
        const customTitleInput = document.querySelector(
          `.custom-title-input[data-id="${id}"]`
        );
        const customTitle = customTitleInput ? customTitleInput.value : "";

        if (isInSlider) sliderCount++;
        sliderItems.push({ id, isInSlider, customTitle });
      });

      if (sliderCount > 5) {
        alert("Vous ne pouvez sélectionner que 5 images dans le slider.");
        return;
      }

      const saveUrl = saveBtn.dataset.saveUrl;
      fetch(saveUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ sliderItems }),
      })
        .then((r) => r.json())
        .then((data) => {
          if (data.success) {
            alert("Le slider a été mis à jour.");
            updateSliderPreview();
          } else {
            alert("Erreur : " + (data.message || "Inconnue"));
          }
        })
        .catch((err) => {
          console.error(err);
          alert("Erreur lors de l’enregistrement.");
        });
    },
    { signal }
  );

  // Premier rendu
  updateSliderPreview();
}

document.addEventListener("DOMContentLoaded", () => {
  const widget = document.querySelector(".comment-widget");
  const header = document.querySelector(".comment-header");
  const closeBtn = document.querySelector(".close-btn");

  if (!widget || !header || !closeBtn) return;

  // Ouverture au clic sur le header
  header.addEventListener("click", () => {
    widget.classList.add("open");
  });

  // Fermeture via la croix
  closeBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    widget.classList.remove("open");
  });

  // Initialisation du formulaire AJAX si présent
  if (typeof initCommentForm === "function") {
    initCommentForm();
  }
});

function initLightboxCommentForm(container, tableauId) {
  const form = container.querySelector("form");
  const commentsList = container.querySelector("#comments_list");

  if (!form || !commentsList) return;

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const formData = new FormData(form);

    fetch(`/tableau/${tableauId}/avis`, {
      method: "POST",
      body: formData,
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          commentsList.innerHTML = data.html;
          form.reset();
          alert(data.message); // message de succès
        } else {
          alert(data.message || "Erreur lors de l'envoi du commentaire.");
        }
      })
      .catch((err) => {
        console.error(err);
        alert("Erreur lors de l'envoi du commentaire.");
      });
  });
}
