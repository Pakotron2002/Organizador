// Sistema de captura y edición de imágenes mejorado
class ImageCapture {
  constructor() {
    this.canvas = null
    this.ctx = null
    this.currentImage = null
    this.cropArea = { x: 0, y: 0, size: 0 }
    this.isDragging = false
    this.dragStart = { x: 0, y: 0 }
    this.isResizing = false
    this.initializeModal()
  }

  initializeModal() {
    // Crear modal de edición de imagen si no existe
    if (!document.getElementById("imageEditModal")) {
      const modalHTML = `
                <div class="modal fade" id="imageEditModal" tabindex="-1" data-bs-backdrop="static">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">✂️ Editar Imagen</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    <div class="image-edit-instructions">
                                        <div class="alert alert-info">
                                            <strong>📝 Instrucciones:</strong><br>
                                            • <strong>Arrastrar</strong> el área azul para moverla<br>
                                            • <strong>Redimensionar</strong> usando las esquinas<br>
                                            • El área seleccionada será tu imagen final
                                        </div>
                                    </div>
                                </div>
                                <div class="canvas-container text-center">
                                    <canvas id="imageEditCanvas" class="image-edit-canvas"></canvas>
                                </div>
                                <div class="mt-3 text-center">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-secondary" id="resetCropBtn">
                                            🔄 Reiniciar
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="centerCropBtn">
                                            🎯 Centrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    ❌ Cancelar
                                </button>
                                <button type="button" class="btn btn-success" id="cropImageBtn">
                                    ✅ Usar Imagen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `
      document.body.insertAdjacentHTML("beforeend", modalHTML)
    }

    this.canvas = document.getElementById("imageEditCanvas")
    this.ctx = this.canvas.getContext("2d")

    // Event listeners para el canvas
    this.canvas.addEventListener("mousedown", this.startInteraction.bind(this))
    this.canvas.addEventListener("mousemove", this.updateInteraction.bind(this))
    this.canvas.addEventListener("mouseup", this.endInteraction.bind(this))

    // Touch events para móvil
    this.canvas.addEventListener("touchstart", this.handleTouch.bind(this))
    this.canvas.addEventListener("touchmove", this.handleTouch.bind(this))
    this.canvas.addEventListener("touchend", this.endInteraction.bind(this))

    // Botones de control
    document.getElementById("cropImageBtn").addEventListener("click", this.cropAndSave.bind(this))
    document.getElementById("resetCropBtn").addEventListener("click", this.resetCrop.bind(this))
    document.getElementById("centerCropBtn").addEventListener("click", this.centerCrop.bind(this))
  }

  handleTouch(e) {
    e.preventDefault()
    const touch = e.touches[0]
    if (!touch) return

    const rect = this.canvas.getBoundingClientRect()
    const mouseEvent = new MouseEvent(e.type.replace("touch", "mouse"), {
      clientX: touch.clientX,
      clientY: touch.clientY,
    })

    if (e.type === "touchstart") this.startInteraction(mouseEvent)
    else if (e.type === "touchmove") this.updateInteraction(mouseEvent)
  }

  getCanvasCoordinates(e) {
    const rect = this.canvas.getBoundingClientRect()
    const scaleX = this.canvas.width / rect.width
    const scaleY = this.canvas.height / rect.height
    return {
      x: (e.clientX - rect.left) * scaleX,
      y: (e.clientY - rect.top) * scaleY,
    }
  }

  isInCropArea(x, y) {
    return (
      x >= this.cropArea.x &&
      x <= this.cropArea.x + this.cropArea.size &&
      y >= this.cropArea.y &&
      y <= this.cropArea.y + this.cropArea.size
    )
  }

  isNearCorner(x, y) {
    const cornerSize = 20
    const corners = [
      { x: this.cropArea.x + this.cropArea.size, y: this.cropArea.y + this.cropArea.size }, // bottom-right
    ]

    for (const corner of corners) {
      if (Math.abs(x - corner.x) < cornerSize && Math.abs(y - corner.y) < cornerSize) {
        return true
      }
    }
    return false
  }

  startInteraction(e) {
    const coords = this.getCanvasCoordinates(e)

    if (this.isNearCorner(coords.x, coords.y)) {
      this.isResizing = true
      this.canvas.style.cursor = "nw-resize"
    } else if (this.isInCropArea(coords.x, coords.y)) {
      this.isDragging = true
      this.dragStart = {
        x: coords.x - this.cropArea.x,
        y: coords.y - this.cropArea.y,
      }
      this.canvas.style.cursor = "move"
    } else {
      // Crear nueva área de recorte
      this.cropArea.x = coords.x
      this.cropArea.y = coords.y
      this.cropArea.size = 0
      this.isResizing = true
    }
  }

  updateInteraction(e) {
    const coords = this.getCanvasCoordinates(e)

    if (this.isResizing) {
      // Redimensionar área de recorte
      const deltaX = Math.abs(coords.x - this.cropArea.x)
      const deltaY = Math.abs(coords.y - this.cropArea.y)
      this.cropArea.size = Math.min(deltaX, deltaY)

      // Limitar al canvas
      const maxSize = Math.min(this.canvas.width - this.cropArea.x, this.canvas.height - this.cropArea.y)
      this.cropArea.size = Math.min(this.cropArea.size, maxSize)
    } else if (this.isDragging) {
      // Mover área de recorte
      const newX = coords.x - this.dragStart.x
      const newY = coords.y - this.dragStart.y

      // Limitar al canvas
      this.cropArea.x = Math.max(0, Math.min(newX, this.canvas.width - this.cropArea.size))
      this.cropArea.y = Math.max(0, Math.min(newY, this.canvas.height - this.cropArea.size))
    } else {
      // Cambiar cursor según posición
      if (this.isNearCorner(coords.x, coords.y)) {
        this.canvas.style.cursor = "nw-resize"
      } else if (this.isInCropArea(coords.x, coords.y)) {
        this.canvas.style.cursor = "move"
      } else {
        this.canvas.style.cursor = "crosshair"
      }
    }

    this.redrawCanvas()
  }

  endInteraction() {
    this.isDragging = false
    this.isResizing = false
    this.canvas.style.cursor = "crosshair"
  }

  resetCrop() {
    const minDimension = Math.min(this.canvas.width, this.canvas.height)
    this.cropArea = {
      x: (this.canvas.width - minDimension) / 2,
      y: (this.canvas.height - minDimension) / 2,
      size: minDimension,
    }
    this.redrawCanvas()
  }

  centerCrop() {
    const centerX = this.canvas.width / 2
    const centerY = this.canvas.height / 2
    const halfSize = this.cropArea.size / 2

    this.cropArea.x = Math.max(0, Math.min(centerX - halfSize, this.canvas.width - this.cropArea.size))
    this.cropArea.y = Math.max(0, Math.min(centerY - halfSize, this.canvas.height - this.cropArea.size))

    this.redrawCanvas()
  }

  redrawCanvas() {
    // Limpiar canvas
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height)

    // Dibujar imagen original
    this.ctx.drawImage(this.currentImage, 0, 0, this.canvas.width, this.canvas.height)

    // Dibujar overlay semitransparente
    this.ctx.fillStyle = "rgba(0, 0, 0, 0.6)"
    this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height)

    // Limpiar área de recorte
    if (this.cropArea.size > 0) {
      this.ctx.clearRect(this.cropArea.x, this.cropArea.y, this.cropArea.size, this.cropArea.size)
      this.ctx.drawImage(
        this.currentImage,
        this.cropArea.x,
        this.cropArea.y,
        this.cropArea.size,
        this.cropArea.size,
        this.cropArea.x,
        this.cropArea.y,
        this.cropArea.size,
        this.cropArea.size,
      )

      // Dibujar borde del área de recorte
      this.ctx.strokeStyle = "#007bff"
      this.ctx.lineWidth = 3
      this.ctx.strokeRect(this.cropArea.x, this.cropArea.y, this.cropArea.size, this.cropArea.size)

      // Dibujar esquinas de redimensionamiento
      this.drawResizeHandle(this.cropArea.x + this.cropArea.size, this.cropArea.y + this.cropArea.size)

      // Dibujar líneas de guía
      this.drawGuideLines()
    }
  }

  drawResizeHandle(x, y) {
    const size = 12
    this.ctx.fillStyle = "#007bff"
    this.ctx.fillRect(x - size / 2, y - size / 2, size, size)
    this.ctx.strokeStyle = "#ffffff"
    this.ctx.lineWidth = 2
    this.ctx.strokeRect(x - size / 2, y - size / 2, size, size)
  }

  drawGuideLines() {
    this.ctx.strokeStyle = "rgba(255, 255, 255, 0.5)"
    this.ctx.lineWidth = 1
    this.ctx.setLineDash([5, 5])

    // Líneas verticales
    const thirdWidth = this.cropArea.size / 3
    for (let i = 1; i < 3; i++) {
      const x = this.cropArea.x + thirdWidth * i
      this.ctx.beginPath()
      this.ctx.moveTo(x, this.cropArea.y)
      this.ctx.lineTo(x, this.cropArea.y + this.cropArea.size)
      this.ctx.stroke()
    }

    // Líneas horizontales
    const thirdHeight = this.cropArea.size / 3
    for (let i = 1; i < 3; i++) {
      const y = this.cropArea.y + thirdHeight * i
      this.ctx.beginPath()
      this.ctx.moveTo(this.cropArea.x, y)
      this.ctx.lineTo(this.cropArea.x + this.cropArea.size, y)
      this.ctx.stroke()
    }

    this.ctx.setLineDash([])
  }

  openImageEditor(file, callback) {
    this.callback = callback

    const reader = new FileReader()
    reader.onload = (e) => {
      const img = new Image()
      img.onload = () => {
        this.currentImage = img

        // Configurar canvas
        const maxSize = 600
        let { width, height } = img

        if (width > height) {
          if (width > maxSize) {
            height = (height * maxSize) / width
            width = maxSize
          }
        } else {
          if (height > maxSize) {
            width = (width * maxSize) / height
            height = maxSize
          }
        }

        this.canvas.width = width
        this.canvas.height = height

        // Inicializar área de recorte en el centro
        const minDimension = Math.min(width, height) * 0.8
        this.cropArea = {
          x: (width - minDimension) / 2,
          y: (height - minDimension) / 2,
          size: minDimension,
        }

        this.redrawCanvas()

        // Mostrar modal
        const modal = window.bootstrap.Modal.getOrCreateInstance(document.getElementById("imageEditModal"))
        modal.show()
      }
      img.src = e.target.result
    }
    reader.readAsDataURL(file)
  }

  cropAndSave() {
    if (this.cropArea.size === 0) {
      alert("Por favor selecciona un área para recortar")
      return
    }

    // Crear canvas para la imagen final
    const finalCanvas = document.createElement("canvas")
    const finalCtx = finalCanvas.getContext("2d")

    // Tamaño final optimizado
    const finalSize = 400
    finalCanvas.width = finalSize
    finalCanvas.height = finalSize

    // Calcular proporciones
    const scaleX = this.currentImage.width / this.canvas.width
    const scaleY = this.currentImage.height / this.canvas.height

    // Dibujar la imagen recortada
    finalCtx.drawImage(
      this.currentImage,
      this.cropArea.x * scaleX,
      this.cropArea.y * scaleY,
      this.cropArea.size * scaleX,
      this.cropArea.size * scaleY,
      0,
      0,
      finalSize,
      finalSize,
    )

    // Convertir a blob con compresión
    finalCanvas.toBlob(
      (blob) => {
        const file = new File([blob], "cropped-image.jpg", { type: "image/jpeg" })
        this.callback(file)

        // Cerrar modal
        const modal = window.bootstrap.Modal.getOrCreateInstance(document.getElementById("imageEditModal"))
        modal.hide()
      },
      "image/jpeg",
      0.85,
    )
  }
}

// Función para crear input de archivo personalizado mejorado
function createImageInput(inputId, callback) {
  const container = document.createElement("div")
  container.className = "image-input-container"
  container.innerHTML = `
        <div class="image-input-options">
            <div class="row g-2">
                <div class="col-6">
                    <button type="button" class="btn btn-outline-primary w-100 image-input-btn" onclick="selectFile('${inputId}')">
                        <div class="d-flex flex-column align-items-center">
                            <span style="font-size: 1.5rem;">📁</span>
                            <small>Seleccionar</small>
                        </div>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-success w-100 image-input-btn" onclick="takePhoto('${inputId}')">
                        <div class="d-flex flex-column align-items-center">
                            <span style="font-size: 1.5rem;">📷</span>
                            <small>Cámara</small>
                        </div>
                    </button>
                </div>
            </div>
            <div class="image-preview mt-2" id="${inputId}_preview" style="display: none;">
                <div class="alert alert-success">
                    <strong>✅ Imagen lista para usar</strong>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="changeImage('${inputId}')">
                        Cambiar
                    </button>
                </div>
            </div>
        </div>
        <input type="file" id="${inputId}_file" accept="image/*" style="display: none;">
        <input type="file" id="${inputId}_camera" accept="image/*" capture="environment" style="display: none;">
        <input type="file" id="${inputId}_final" name="foto" style="display: none;">
    `

  const fileInput = container.querySelector(`#${inputId}_file`)
  const cameraInput = container.querySelector(`#${inputId}_camera`)

  const handleImageSelection = (file) => {
    window.imageCapture.openImageEditor(file, (croppedFile) => {
      const dataTransfer = new DataTransfer()
      dataTransfer.items.add(croppedFile)
      document.getElementById(`${inputId}_final`).files = dataTransfer.files

      // Mostrar preview
      document.getElementById(`${inputId}_preview`).style.display = "block"

      callback(croppedFile)
    })
  }

  fileInput.addEventListener("change", (e) => {
    if (e.target.files[0]) {
      handleImageSelection(e.target.files[0])
    }
  })

  cameraInput.addEventListener("change", (e) => {
    if (e.target.files[0]) {
      handleImageSelection(e.target.files[0])
    }
  })

  return container
}

function selectFile(inputId) {
  document.getElementById(`${inputId}_file`).click()
}

function takePhoto(inputId) {
  document.getElementById(`${inputId}_camera`).click()
}

function changeImage(inputId) {
  document.getElementById(`${inputId}_preview`).style.display = "none"
  document.getElementById(`${inputId}_final`).value = ""
}

// Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  window.imageCapture = new ImageCapture()
})
