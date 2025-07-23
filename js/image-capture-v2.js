// Sistema de captura y edición de imágenes con editor 1:1
class ImageCaptureV2 {
  constructor() {
    this.canvas = null
    this.ctx = null
    this.previewCanvas = null
    this.previewCtx = null
    this.currentImage = null
    this.cropArea = { x: 0, y: 0, width: 0, height: 0 }
    this.isDragging = false
    this.isResizing = false
    this.dragStart = { x: 0, y: 0 }
    this.resizeHandle = null
    this.scale = 1
    this.minCropSize = 50
    this.stream = null
    this.video = null
    this.currentInputId = null
    this.callback = null
    this.initializeModal()
  }

  initializeModal() {
    // Crear modal de edición de imagen si no existe
    if (!document.getElementById("imageEditModalV2")) {
      const modalHTML = `
                <div class="modal" id="imageEditModalV2">
                    <div class="modal-content" style="max-width: 900px; width: 95%;">
                        <div class="modal-header">
                            <h3><i class="fas fa-crop"></i> Editor de Imagen</h3>
                            <span class="close" onclick="window.imageCapture.closeEditor()">&times;</span>
                        </div>
                        <div class="modal-body" style="padding: 0;">
                            <div style="display: flex; min-height: 500px;">
                                <!-- Panel de edición -->
                                <div style="flex: 2; padding: 1rem; border-right: 1px solid #eee;">
                                    <div class="editor-toolbar" style="margin-bottom: 1rem; text-align: center;">
                                        <div style="display: inline-flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="window.imageCapture.resetCrop()">
                                                🔄 Reiniciar
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="window.imageCapture.centerCrop()">
                                                🎯 Centrar
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="window.imageCapture.fitCrop()">
                                                📐 Ajustar
                                            </button>
                                        </div>
                                        <div>
                                            <small class="text-muted" id="cropSizeInfo">Selecciona un área cuadrada</small>
                                        </div>
                                    </div>
                                    <div class="canvas-wrapper" style="position: relative; display: inline-block; border: 2px solid #ddd; border-radius: 8px; overflow: hidden;">
                                        <canvas id="imageEditCanvasV2" style="display: block;"></canvas>
                                        <div class="crop-overlay" id="cropOverlay" style="position: absolute; top: 0; left: 0; pointer-events: none;">
                                            <div class="crop-selection" id="cropSelection" style="position: absolute; border: 2px solid #007bff; background: rgba(0, 123, 255, 0.1); cursor: move; pointer-events: all;">
                                                <div class="crop-handle nw" data-handle="nw" style="position: absolute; top: -5px; left: -5px; width: 10px; height: 10px; background: #007bff; cursor: nw-resize; pointer-events: all;"></div>
                                                <div class="crop-handle ne" data-handle="ne" style="position: absolute; top: -5px; right: -5px; width: 10px; height: 10px; background: #007bff; cursor: ne-resize; pointer-events: all;"></div>
                                                <div class="crop-handle sw" data-handle="sw" style="position: absolute; bottom: -5px; left: -5px; width: 10px; height: 10px; background: #007bff; cursor: sw-resize; pointer-events: all;"></div>
                                                <div class="crop-handle se" data-handle="se" style="position: absolute; bottom: -5px; right: -5px; width: 10px; height: 10px; background: #007bff; cursor: se-resize; pointer-events: all;"></div>
                                                <div class="crop-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #007bff; pointer-events: none;">
                                                    <i class="fas fa-arrows-alt"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Panel de preview -->
                                <div style="flex: 1; padding: 1rem; background: #f8f9fa;">
                                    <div class="preview-header" style="text-align: center; margin-bottom: 1rem;">
                                        <h4>👁️ Vista Previa</h4>
                                        <small class="text-muted">Resultado final (400x400px)</small>
                                    </div>
                                    <div class="preview-container" style="text-align: center; margin-bottom: 1rem;">
                                        <canvas id="previewCanvasV2" width="200" height="200" style="border: 2px solid #ddd; border-radius: 8px; background: white;"></canvas>
                                    </div>
                                    <div class="preview-info" style="text-align: center;">
                                        <div style="display: flex; justify-content: space-around;">
                                            <div>
                                                <div style="font-size: 1.2rem; font-weight: bold; color: #007bff;" id="previewWidth">400</div>
                                                <div style="font-size: 0.8rem; color: #666;">Ancho</div>
                                            </div>
                                            <div>
                                                <div style="font-size: 1.2rem; font-weight: bold; color: #007bff;" id="previewHeight">400</div>
                                                <div style="font-size: 0.8rem; color: #666;">Alto</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div style="flex: 1; text-align: left;">
                                <small class="text-muted">
                                    💡 Arrastra para mover • Usa las esquinas para redimensionar
                                </small>
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="window.imageCapture.closeEditor()">
                                ❌ Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" onclick="window.imageCapture.cropAndSave()">
                                ✅ Usar Imagen
                            </button>
                        </div>
                    </div>
                </div>
            `
      document.body.insertAdjacentHTML("beforeend", modalHTML)
    }

    this.canvas = document.getElementById("imageEditCanvasV2")
    this.ctx = this.canvas.getContext("2d")
    this.previewCanvas = document.getElementById("previewCanvasV2")
    this.previewCtx = this.previewCanvas.getContext("2d")

    this.cropOverlay = document.getElementById("cropOverlay")
    this.cropSelection = document.getElementById("cropSelection")

    this.initializeEventListeners()
  }

  initializeEventListeners() {
    // Eventos del área de recorte
    this.cropSelection.addEventListener("mousedown", this.startDrag.bind(this))
    document.addEventListener("mousemove", this.onMouseMove.bind(this))
    document.addEventListener("mouseup", this.endDrag.bind(this))

    // Eventos táctiles
    this.cropSelection.addEventListener("touchstart", this.startDrag.bind(this))
    document.addEventListener("touchmove", this.onTouchMove.bind(this))
    document.addEventListener("touchend", this.endDrag.bind(this))

    // Eventos de los handles de redimensionamiento
    const handles = this.cropSelection.querySelectorAll(".crop-handle")
    handles.forEach((handle) => {
      handle.addEventListener("mousedown", (e) => this.startResize(e, handle.dataset.handle))
      handle.addEventListener("touchstart", (e) => this.startResize(e, handle.dataset.handle))
    })
  }

  async captureImage(inputId, callback) {
    this.currentInputId = inputId
    this.callback = callback

    try {
      this.stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: "environment" },
      })

      // Crear modal de cámara
      this.createCameraModal()
      this.video = document.getElementById("cameraVideoV2")
      this.video.srcObject = this.stream
      this.video.play()

      window.openModal("cameraModalV2")
    } catch (error) {
      console.error("Error al acceder a la cámara:", error)
      alert("No se pudo acceder a la cámara. Por favor, usa el selector de archivos.")
    }
  }

  createCameraModal() {
    if (!document.getElementById("cameraModalV2")) {
      const modalHTML = `
                <div class="modal" id="cameraModalV2">
                    <div class="modal-content" style="max-width: 600px;">
                        <div class="modal-header">
                            <h3><i class="fas fa-camera"></i> Capturar Foto</h3>
                            <span class="close" onclick="window.imageCapture.closeCameraModal()">&times;</span>
                        </div>
                        <div class="modal-body" style="text-align: center;">
                            <video id="cameraVideoV2" autoplay style="width: 100%; max-width: 500px; border-radius: 8px; margin-bottom: 1rem;"></video>
                            <div>
                                <button type="button" class="btn btn-primary" onclick="window.imageCapture.takePicture()">
                                    <i class="fas fa-camera"></i> Tomar Foto
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="window.imageCapture.closeCameraModal()">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `
      document.body.insertAdjacentHTML("beforeend", modalHTML)
    }
  }

  takePicture() {
    const video = document.getElementById("cameraVideoV2")
    const canvas = document.createElement("canvas")
    const ctx = canvas.getContext("2d")

    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    ctx.drawImage(video, 0, 0)

    canvas.toBlob(
      (blob) => {
        this.closeCameraModal()
        this.openEditor(blob)
      },
      "image/jpeg",
      0.9,
    )
  }

  closeCameraModal() {
    if (this.stream) {
      this.stream.getTracks().forEach((track) => track.stop())
      this.stream = null
    }
    window.closeModal("cameraModalV2")
  }

  openEditor(imageBlob) {
    const img = new Image()
    img.onload = () => {
      this.currentImage = img
      this.setupCanvas()
      this.initializeCrop()
      this.updatePreview()
      window.openModal("imageEditModalV2")
    }
    img.src = URL.createObjectURL(imageBlob)
  }

  setupCanvas() {
    const maxWidth = 600
    const maxHeight = 400

    const { width, height } = this.currentImage

    // Calcular escala para ajustar la imagen al canvas
    const scaleX = maxWidth / width
    const scaleY = maxHeight / height
    this.scale = Math.min(scaleX, scaleY, 1)

    this.canvas.width = width * this.scale
    this.canvas.height = height * this.scale

    // Dibujar imagen escalada
    this.ctx.drawImage(this.currentImage, 0, 0, this.canvas.width, this.canvas.height)

    // Ajustar overlay
    this.cropOverlay.style.width = this.canvas.width + "px"
    this.cropOverlay.style.height = this.canvas.height + "px"
  }

  initializeCrop() {
    const size = Math.min(this.canvas.width, this.canvas.height) * 0.8
    const x = (this.canvas.width - size) / 2
    const y = (this.canvas.height - size) / 2

    this.cropArea = { x, y, width: size, height: size }
    this.updateCropSelection()
  }

  updateCropSelection() {
    this.cropSelection.style.left = this.cropArea.x + "px"
    this.cropSelection.style.top = this.cropArea.y + "px"
    this.cropSelection.style.width = this.cropArea.width + "px"
    this.cropSelection.style.height = this.cropArea.height + "px"

    // Actualizar información
    const realSize = Math.round(this.cropArea.width / this.scale)
    document.getElementById("cropSizeInfo").textContent = `Área: ${realSize}x${realSize}px`

    this.updatePreview()
  }

  updatePreview() {
    if (!this.currentImage) return

    // Limpiar canvas de preview
    this.previewCtx.clearRect(0, 0, 200, 200)

    // Calcular área de recorte en la imagen original
    const sourceX = this.cropArea.x / this.scale
    const sourceY = this.cropArea.y / this.scale
    const sourceSize = this.cropArea.width / this.scale

    // Dibujar imagen recortada en el preview
    this.previewCtx.drawImage(this.currentImage, sourceX, sourceY, sourceSize, sourceSize, 0, 0, 200, 200)
  }

  startDrag(e) {
    e.preventDefault()

    if (e.target.classList.contains("crop-handle")) {
      return // Los handles tienen su propio manejo
    }

    this.isDragging = true
    const rect = this.cropOverlay.getBoundingClientRect()
    const clientX = e.clientX || (e.touches && e.touches[0].clientX)
    const clientY = e.clientY || (e.touches && e.touches[0].clientY)

    this.dragStart = {
      x: clientX - rect.left - this.cropArea.x,
      y: clientY - rect.top - this.cropArea.y,
    }
  }

  startResize(e, handle) {
    e.preventDefault()
    e.stopPropagation()

    this.isResizing = true
    this.resizeHandle = handle

    const rect = this.cropOverlay.getBoundingClientRect()
    const clientX = e.clientX || (e.touches && e.touches[0].clientX)
    const clientY = e.clientY || (e.touches && e.touches[0].clientY)

    this.dragStart = {
      x: clientX - rect.left,
      y: clientY - rect.top,
      cropX: this.cropArea.x,
      cropY: this.cropArea.y,
      cropWidth: this.cropArea.width,
      cropHeight: this.cropArea.height,
    }
  }

  onMouseMove(e) {
    this.handleMove(e.clientX, e.clientY)
  }

  onTouchMove(e) {
    if (e.touches && e.touches[0]) {
      e.preventDefault()
      this.handleMove(e.touches[0].clientX, e.touches[0].clientY)
    }
  }

  handleMove(clientX, clientY) {
    if (!this.isDragging && !this.isResizing) return

    const rect = this.cropOverlay.getBoundingClientRect()
    const x = clientX - rect.left
    const y = clientY - rect.top

    if (this.isDragging) {
      let newX = x - this.dragStart.x
      let newY = y - this.dragStart.y

      // Limitar movimiento dentro del canvas
      newX = Math.max(0, Math.min(newX, this.canvas.width - this.cropArea.width))
      newY = Math.max(0, Math.min(newY, this.canvas.height - this.cropArea.height))

      this.cropArea.x = newX
      this.cropArea.y = newY
    } else if (this.isResizing) {
      this.handleResize(x, y)
    }

    this.updateCropSelection()
  }

  handleResize(x, y) {
    const deltaX = x - this.dragStart.x
    const deltaY = y - this.dragStart.y

    let newX = this.dragStart.cropX
    let newY = this.dragStart.cropY
    let newSize = this.dragStart.cropWidth

    switch (this.resizeHandle) {
      case "nw":
        const deltaMin = Math.min(deltaX, deltaY)
        newX = this.dragStart.cropX + deltaMin
        newY = this.dragStart.cropY + deltaMin
        newSize = this.dragStart.cropWidth - deltaMin
        break
      case "ne":
        const deltaNE = Math.max(deltaX, -deltaY)
        newY = this.dragStart.cropY - deltaNE
        newSize = this.dragStart.cropWidth + deltaNE
        break
      case "sw":
        const deltaSW = Math.max(-deltaX, deltaY)
        newX = this.dragStart.cropX - deltaSW
        newSize = this.dragStart.cropWidth + deltaSW
        break
      case "se":
        const deltaSE = Math.max(deltaX, deltaY)
        newSize = this.dragStart.cropWidth + deltaSE
        break
    }

    // Aplicar límites
    newSize = Math.max(this.minCropSize, newSize)
    newX = Math.max(0, Math.min(newX, this.canvas.width - newSize))
    newY = Math.max(0, Math.min(newY, this.canvas.height - newSize))

    // Asegurar que no se salga del canvas
    if (newX + newSize > this.canvas.width) {
      newSize = this.canvas.width - newX
    }
    if (newY + newSize > this.canvas.height) {
      newSize = this.canvas.height - newY
    }

    this.cropArea = { x: newX, y: newY, width: newSize, height: newSize }
  }

  endDrag() {
    this.isDragging = false
    this.isResizing = false
    this.resizeHandle = null
  }

  resetCrop() {
    this.initializeCrop()
  }

  centerCrop() {
    const size = this.cropArea.width
    this.cropArea.x = (this.canvas.width - size) / 2
    this.cropArea.y = (this.canvas.height - size) / 2
    this.updateCropSelection()
  }

  fitCrop() {
    const size = Math.min(this.canvas.width, this.canvas.height) * 0.95
    this.cropArea = {
      x: (this.canvas.width - size) / 2,
      y: (this.canvas.height - size) / 2,
      width: size,
      height: size,
    }
    this.updateCropSelection()
  }

  cropAndSave() {
    // Crear canvas final de 400x400
    const finalCanvas = document.createElement("canvas")
    const finalCtx = finalCanvas.getContext("2d")
    finalCanvas.width = 400
    finalCanvas.height = 400

    // Calcular área de recorte en la imagen original
    const sourceX = this.cropArea.x / this.scale
    const sourceY = this.cropArea.y / this.scale
    const sourceSize = this.cropArea.width / this.scale

    // Dibujar imagen recortada
    finalCtx.drawImage(this.currentImage, sourceX, sourceY, sourceSize, sourceSize, 0, 0, 400, 400)

    // Convertir a blob y crear archivo
    finalCanvas.toBlob(
      (blob) => {
        const file = new File([blob], "cropped-image.jpg", { type: "image/jpeg" })

        // Crear un nuevo input file con el archivo
        const dt = new DataTransfer()
        dt.items.add(file)

        const input = document.getElementById(this.currentInputId)
        if (input) {
          input.files = dt.files

          // Disparar evento change
          const event = new Event("change", { bubbles: true })
          input.dispatchEvent(event)

          // Mostrar preview
          const previewId = this.currentInputId + "-preview"
          const preview = document.getElementById(previewId)
          if (preview) {
            preview.innerHTML = `<img src="${URL.createObjectURL(blob)}" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px;">`
          }
        }

        if (this.callback) {
          this.callback(file)
        }

        this.closeEditor()
      },
      "image/jpeg",
      0.9,
    )
  }

  closeEditor() {
    window.closeModal("imageEditModalV2")
    this.currentImage = null
    this.currentInputId = null
    this.callback = null
  }
}

// Crear instancia global
window.imageCapture = new ImageCaptureV2()

// Función global para capturar imagen
function captureImage(inputId, callback) {
  window.imageCapture.captureImage(inputId, callback)
}

// Función para manejar selección de archivo con editor
function handleFileSelect(inputId) {
  const input = document.getElementById(inputId)
  if (input && input.files && input.files[0]) {
    const file = input.files[0]
    if (file.type.startsWith("image/")) {
      window.imageCapture.currentInputId = inputId
      window.imageCapture.openEditor(file)
    }
  }
}

// Funciones globales para abrir y cerrar modales
window.openModal = (modalId) => {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.style.display = "block"
  }
}

window.closeModal = (modalId) => {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.style.display = "none"
  }
}

// Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  // Interceptar cambios en inputs de archivo de imagen
  const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]')
  imageInputs.forEach((input) => {
    input.addEventListener("change", function (e) {
      if (this.files && this.files[0]) {
        // Prevenir el comportamiento por defecto
        e.preventDefault()

        // Abrir editor
        handleFileSelect(this.id)
      }
    })
  })
})
