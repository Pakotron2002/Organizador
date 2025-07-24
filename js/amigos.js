// Funcionalidad específica para la página de amigos
class AmigosPage {
  constructor() {
    this.initializeAmigosForm()
    this.initializePrestarModal()
  }

  initializeAmigosForm() {
    const amigoForm = document.getElementById("amigoForm")
    if (amigoForm) {
      amigoForm.addEventListener("submit", async (e) => {
        e.preventDefault()
        await this.submitAmigoForm(amigoForm)
      })
    }
  }

  async submitAmigoForm(form) {
    try {
      const formData = new FormData(form)

      const response = await fetch("api/amigos.php", {
        method: "POST",
        body: formData,
      })

      const data = await response.json()

      if (data.success) {
        window.app.showToast("Amigo agregado exitosamente", "success")
        const Modal = window.bootstrap.Modal
        Modal.getInstance(document.getElementById("amigoModal")).hide()
        form.reset()
        setTimeout(() => location.reload(), 1000)
      } else {
        window.app.showToast(data.message || "Error al agregar amigo", "error")
      }
    } catch (error) {
      console.error("Error:", error)
      window.app.showToast("Error de conexión", "error")
    }
  }

  initializePrestarModal() {
    const prestarForm = document.getElementById("prestarForm")
    const buscarObjeto = document.getElementById("buscar_objeto")
    const objetosDisponibles = document.getElementById("objetos_disponibles")

    if (prestarForm) {
      prestarForm.addEventListener("submit", async (e) => {
        e.preventDefault()
        await this.prestarObjeto()
      })
    }

    if (buscarObjeto) {
      let searchTimeout
      buscarObjeto.addEventListener("input", (e) => {
        clearTimeout(searchTimeout)
        const query = e.target.value.trim()

        if (query.length < 2) {
          objetosDisponibles.innerHTML = ""
          return
        }

        searchTimeout = setTimeout(() => {
          this.buscarObjetosDisponibles(query)
        }, 300)
      })
    }
  }

  async buscarObjetosDisponibles(query) {
    try {
      const response = await fetch("api/objetos_disponibles.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ query: query }),
      })

      const data = await response.json()
      this.mostrarObjetosDisponibles(data)
    } catch (error) {
      console.error("Error:", error)
    }
  }

  mostrarObjetosDisponibles(objetos) {
    const objetosDisponibles = document.getElementById("objetos_disponibles")

    if (objetos.length === 0) {
      objetosDisponibles.innerHTML = '<small class="text-muted">No se encontraron objetos disponibles</small>'
      return
    }

    objetosDisponibles.innerHTML = objetos
      .map(
        (objeto) => `
            <div class="border rounded p-2 mb-2 cursor-pointer objeto-disponible" 
                 data-id="${objeto.id}" data-nombre="${objeto.nombre}">
                <div class="d-flex align-items-center">
                    <img src="${objeto.foto_url || "assets/images/default-object.jpg"}" 
                         alt="${objeto.nombre}" style="width: 40px; height: 40px; border-radius: 4px; margin-right: 0.5rem;">
                    <div>
                        <strong>${objeto.nombre}</strong>
                        <br><small class="text-muted">${objeto.descripcion || ""}</small>
                    </div>
                </div>
            </div>
        `,
      )
      .join("")

    // Agregar event listeners a los objetos
    document.querySelectorAll(".objeto-disponible").forEach((elemento) => {
      elemento.addEventListener("click", () => {
        this.seleccionarObjeto(elemento.dataset.id, elemento.dataset.nombre)
      })
    })
  }

  seleccionarObjeto(id, nombre) {
    document.getElementById("objeto_id").value = id
    document.getElementById("objeto_nombre").textContent = nombre
    document.getElementById("objeto_seleccionado").style.display = "block"
    document.getElementById("btn_prestar").disabled = false
    document.getElementById("objetos_disponibles").innerHTML = ""
    document.getElementById("buscar_objeto").value = nombre
  }

  async prestarObjeto() {
    try {
      const formData = new FormData(document.getElementById("prestarForm"))

      const response = await fetch("api/prestamos.php", {
        method: "POST",
        body: formData,
      })

      const data = await response.json()

      if (data.success) {
        window.app.showToast("Objeto prestado exitosamente", "success")
        const Modal = window.bootstrap.Modal
        Modal.getInstance(document.getElementById("prestarModal")).hide()
        this.resetPrestarForm()
        setTimeout(() => location.reload(), 1000)
      } else {
        window.app.showToast(data.message || "Error al prestar objeto", "error")
      }
    } catch (error) {
      console.error("Error:", error)
      window.app.showToast("Error de conexión", "error")
    }
  }

  resetPrestarForm() {
    document.getElementById("prestarForm").reset()
    document.getElementById("objeto_seleccionado").style.display = "none"
    document.getElementById("btn_prestar").disabled = true
    document.getElementById("objetos_disponibles").innerHTML = ""
  }
}

// Funciones globales
function prestarObjeto(amigoId) {
  document.getElementById("amigo_id").value = amigoId
  const Modal = window.bootstrap.Modal
  const modal = new Modal(document.getElementById("prestarModal"))
  modal.show()
}

async function devolverObjeto(prestamoId) {
  if (!confirm("¿Confirmar devolución del objeto?")) return

  try {
    const response = await fetch("api/prestamos.php", {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ id: prestamoId }),
    })

    const data = await response.json()

    if (data.success) {
      window.app.showToast("Objeto devuelto exitosamente", "success")
      setTimeout(() => location.reload(), 1000)
    } else {
      window.app.showToast(data.message || "Error al devolver objeto", "error")
    }
  } catch (error) {
    console.error("Error:", error)
    window.app.showToast("Error de conexión", "error")
  }
}

function deleteAmigo(id) {
  window.app.confirmDelete("¿Estás seguro de eliminar este amigo?", async () => {
    try {
      const response = await fetch("api/amigos.php", {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ id: id }),
      })

      const data = await response.json()

      if (data.success) {
        window.app.showToast("Amigo eliminado exitosamente", "success")
        setTimeout(() => location.reload(), 1000)
      } else {
        window.app.showToast(data.message || "Error al eliminar", "error")
      }
    } catch (error) {
      console.error("Error:", error)
      window.app.showToast("Error de conexión", "error")
    }
  })
}

// Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  new AmigosPage()
})
