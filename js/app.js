// Funciones globales para la aplicación

// Función para abrir modales
function openModal(modalId) {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.classList.add("show")
    modal.style.display = "flex"

    // Disparar evento personalizado
    const event = new CustomEvent("modalOpened")
    modal.dispatchEvent(event)
  }
}

// Función para cerrar modales
function closeModal(modalId) {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.classList.remove("show")
    modal.style.display = "none"

    // Disparar evento personalizado
    const event = new CustomEvent("modalClosed")
    modal.dispatchEvent(event)
  }
}

// Cerrar modal al hacer clic fuera de él
document.addEventListener("click", (e) => {
  if (e.target.classList.contains("modal")) {
    const modalId = e.target.id
    closeModal(modalId)
  }
})

// Cerrar modal con la tecla Escape
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    const openModal = document.querySelector(".modal.show")
    if (openModal) {
      closeModal(openModal.id)
    }
  }
})

// Función para mostrar alertas temporales
function showAlert(message, type = "success", duration = 3000) {
  const alert = document.createElement("div")
  alert.className = `alert alert-${type}`
  alert.style.position = "fixed"
  alert.style.top = "20px"
  alert.style.right = "20px"
  alert.style.zIndex = "9999"
  alert.style.minWidth = "300px"
  alert.style.animation = "slideInRight 0.3s ease"
  alert.textContent = message

  document.body.appendChild(alert)

  // Remover después del tiempo especificado
  setTimeout(() => {
    alert.style.animation = "slideOutRight 0.3s ease"
    setTimeout(() => {
      if (alert.parentNode) {
        alert.parentNode.removeChild(alert)
      }
    }, 300)
  }, duration)
}

// Animaciones CSS para las alertas
const style = document.createElement("style")
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`
document.head.appendChild(style)

// Función para confirmar eliminaciones
function confirmDelete(message = "¿Estás seguro de que quieres eliminar este elemento?") {
  return confirm(message)
}

// Función para formatear fechas
function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString("es-ES", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  })
}

// Función para formatear fecha y hora
function formatDateTime(dateString) {
  const date = new Date(dateString)
  return date.toLocaleString("es-ES", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  })
}

// Función para validar formularios
function validateForm(formId) {
  const form = document.getElementById(formId)
  if (!form) return false

  const requiredFields = form.querySelectorAll("[required]")
  let isValid = true

  requiredFields.forEach((field) => {
    if (!field.value.trim()) {
      field.classList.add("is-invalid")
      isValid = false
    } else {
      field.classList.remove("is-invalid")
    }
  })

  return isValid
}

// Función para limpiar formularios
function clearForm(formId) {
  const form = document.getElementById(formId)
  if (form) {
    form.reset()
    // Limpiar clases de validación
    const fields = form.querySelectorAll(".is-invalid")
    fields.forEach((field) => {
      field.classList.remove("is-invalid")
    })
    // Limpiar previsualizaciones de imagen
    const previews = form.querySelectorAll(".image-preview")
    previews.forEach((preview) => {
      preview.innerHTML = ""
    })
  }
}

// Función para previsualizar imágenes
function previewImage(input, previewId) {
  const file = input.files[0]
  const preview = document.getElementById(previewId)

  if (file && preview) {
    const reader = new FileReader()
    reader.onload = (e) => {
      preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">`
    }
    reader.readAsDataURL(file)
  } else if (preview) {
    preview.innerHTML = ""
  }
}

// Función para debounce (útil para búsquedas)
function debounce(func, wait, immediate) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      timeout = null
      if (!immediate) func(...args)
    }
    const callNow = immediate && !timeout
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
    if (callNow) func(...args)
  }
}

// Función para hacer peticiones AJAX
async function makeRequest(url, options = {}) {
  try {
    const response = await fetch(url, {
      headers: {
        "Content-Type": "application/json",
        ...options.headers,
      },
      ...options,
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    return await response.json()
  } catch (error) {
    console.error("Error en la petición:", error)
    showAlert("Error en la conexión", "error")
    throw error
  }
}

// Función para copiar texto al portapapeles
async function copyToClipboard(text) {
  try {
    await navigator.clipboard.writeText(text)
    showAlert("Texto copiado al portapapeles", "success", 2000)
  } catch (err) {
    console.error("Error al copiar:", err)
    showAlert("Error al copiar texto", "error")
  }
}

// Función para detectar dispositivos móviles
function isMobile() {
  return window.innerWidth <= 768
}

// Función para scroll suave
function smoothScrollTo(element) {
  if (typeof element === "string") {
    element = document.querySelector(element)
  }

  if (element) {
    element.scrollIntoView({
      behavior: "smooth",
      block: "start",
    })
  }
}

// Inicialización cuando el DOM está listo
document.addEventListener("DOMContentLoaded", () => {
  // Inicializar tooltips si existen
  const tooltips = document.querySelectorAll("[data-tooltip]")
  tooltips.forEach((tooltip) => {
    tooltip.addEventListener("mouseenter", () => {
      // Implementar tooltip personalizado si es necesario
    })
  })

  // Inicializar validación de formularios en tiempo real
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    const requiredFields = form.querySelectorAll("[required]")
    requiredFields.forEach((field) => {
      field.addEventListener("blur", function () {
        if (!this.value.trim()) {
          this.classList.add("is-invalid")
        } else {
          this.classList.remove("is-invalid")
        }
      })
    })
  })

  // Inicializar previsualización de imágenes
  const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]')
  imageInputs.forEach((input) => {
    input.addEventListener("change", function () {
      const previewId = this.id + "-preview"
      previewImage(this, previewId)
    })
  })

  // Manejar enlaces de confirmación
  const confirmLinks = document.querySelectorAll("[data-confirm]")
  confirmLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      const message = this.getAttribute("data-confirm")
      if (!confirm(message)) {
        e.preventDefault()
      }
    })
  })
})

// Función para manejar errores globales
window.addEventListener("error", (e) => {
  console.error("Error global:", e.error)
  // En producción, podrías enviar esto a un servicio de logging
})

// Función para manejar promesas rechazadas
window.addEventListener("unhandledrejection", (e) => {
  console.error("Promesa rechazada:", e.reason)
  // En producción, podrías enviar esto a un servicio de logging
})
