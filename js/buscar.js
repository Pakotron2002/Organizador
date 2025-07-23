// Funcionalidad específica para la página de búsqueda
class BuscarPage {
  constructor() {
    this.initializeAdvancedSearch()
  }

  initializeAdvancedSearch() {
    const searchInput = document.getElementById("searchInput")
    const filtroEstado = document.getElementById("filtroEstado")
    const filtroUbicacion = document.getElementById("filtroUbicacion")
    const resultadosContainer = document.getElementById("resultadosContainer")
    const resultadosBusqueda = document.getElementById("resultadosBusqueda")

    let searchTimeout

    // Función para realizar búsqueda avanzada
    const performAdvancedSearch = () => {
      const query = searchInput.value.trim()
      const estado = filtroEstado.value
      const ubicacion = filtroUbicacion.value

      if (query.length < 3 && !estado && !ubicacion) {
        resultadosContainer.style.display = "none"
        return
      }

      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(async () => {
        try {
          const response = await fetch("api/buscar_avanzada.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              query: query,
              estado: estado,
              ubicacion: ubicacion,
            }),
          })

          const data = await response.json()
          this.displayAdvancedResults(data)
        } catch (error) {
          console.error("Error en búsqueda avanzada:", error)
        }
      }, 300)
    }

    // Event listeners
    searchInput.addEventListener("input", performAdvancedSearch)
    filtroEstado.addEventListener("change", performAdvancedSearch)
    filtroUbicacion.addEventListener("change", performAdvancedSearch)
  }

  displayAdvancedResults(results) {
    const resultadosContainer = document.getElementById("resultadosContainer")
    const resultadosBusqueda = document.getElementById("resultadosBusqueda")

    if (results.length === 0) {
      resultadosBusqueda.innerHTML =
        '<div class="col-12"><div class="alert alert-info">No se encontraron objetos</div></div>'
    } else {
      resultadosBusqueda.innerHTML = results
        .map(
          (item) => `
                <div class="col-12">
                    <div class="item-card" onclick="location.href='objeto.php?id=${item.id}'">
                        <img src="${item.foto_url || "assets/images/default-object.jpg"}" 
                             alt="${item.nombre}" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">
                        <div class="item-info">
                            <h6>${item.nombre}</h6>
                            <p class="text-muted small">${item.descripcion || ""}</p>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <small class="text-primary">📍 ${item.ubicacion_nombre}</small>
                                <small class="text-info">Cantidad: ${item.cantidad}</small>
                                ${
                                  item.amigo_nombre
                                    ? `<span class="prestado-badge">Prestado a ${item.amigo_nombre}</span>`
                                    : '<span class="disponible-badge">Disponible</span>'
                                }
                            </div>
                        </div>
                    </div>
                </div>
            `,
        )
        .join("")
    }

    resultadosContainer.style.display = "block"
  }
}

// Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  new BuscarPage()
})
