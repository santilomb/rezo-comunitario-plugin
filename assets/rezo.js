// Definir funciones globales
window.rezoFunctions = {
  mostrarFormulario: () => {
    const modal = document.getElementById("formulario-rezos")
    if (modal) {
      modal.style.display = "block"
      document.body.style.overflow = "hidden"
      window.rezoFunctions.generarCaptcha()
    }
  },

  cerrarFormulario: () => {
    const modal = document.getElementById("formulario-rezos")
    if (modal) {
      modal.style.display = "none"
      document.body.style.overflow = "auto"
      window.rezoFunctions.resetearFormulario()
    }
  },

  cerrarGracias: () => {
    const modal = document.getElementById("modal-gracias")
    if (modal) {
      modal.style.display = "none"
      document.body.style.overflow = "auto"
    }
  },

  resetearFormulario: () => {
    if (typeof window.jQuery !== "undefined") {
      const $ = window.jQuery
      $(".opcion-cantidad").removeClass("selected")
      $("#cantidad-personalizada").hide()
      $("#cantidad-otro").val("")
      $("#captcha-respuesta").val("")
      $(".btn-enviar").prop("disabled", true)
    }
    window.rezoFunctions.generarCaptcha()
  },

  generarCaptcha: () => {
    const num1 = Math.floor(Math.random() * 10) + 1
    const num2 = Math.floor(Math.random() * 10) + 1
    const resultado = num1 + num2

    const preguntaElement = document.getElementById("captcha-pregunta")
    const resultadoElement = document.getElementById("captcha-resultado")

    if (preguntaElement && resultadoElement) {
      preguntaElement.textContent = `¿Cuánto es ${num1} + ${num2}?`
      resultadoElement.value = resultado
    }
  },

  validarFormulario: () => {
    if (typeof window.jQuery === "undefined") return

    const $ = window.jQuery
    const cantidadSeleccionada = $(".opcion-cantidad.selected").length > 0
    const cantidadPersonalizada = $("#cantidad-personalizada").is(":visible") ? $("#cantidad-otro").val() > 0 : true
    const captchaCompleto = $("#captcha-respuesta").val() !== ""

    const esValido = cantidadSeleccionada && cantidadPersonalizada && captchaCompleto
    $(".btn-enviar").prop("disabled", !esValido)
  },

  obtenerCantidad: () => {
    if (typeof window.jQuery === "undefined") return 0

    const $ = window.jQuery
    const seleccionada = $(".opcion-cantidad.selected")
    const cantidad = seleccionada.data("cantidad")

    if (cantidad === "otro") {
      return Number.parseInt($("#cantidad-otro").val()) || 0
    }
    return cantidad || 0
  },

  enviarRezos: () => {
    if (typeof window.jQuery === "undefined" || typeof window.rezo_ajax === "undefined") {
      return
    }

    const $ = window.jQuery
    const intencionId = $(".rezo-intencion-detalle").data("intencion-id")
    const cantidad = window.rezoFunctions.obtenerCantidad()
    const captchaRespuesta = $("#captcha-respuesta").val()
    const captchaResultado = $("#captcha-resultado").val()

    $(".btn-enviar").prop("disabled", true).text("Enviando...")

    $.ajax({
      url: window.rezo_ajax.ajax_url,
      type: "POST",
      data: {
        action: "agregar_rezos",
        intencion_id: intencionId,
        cantidad: cantidad,
        captcha_response: captchaRespuesta,
        captcha_resultado: captchaResultado,
        nonce: window.rezo_ajax.nonce,
      },
      success: (response) => {
        if (response.success) {
          window.rezoFunctions.actualizarProgreso(response.data.avemarias_actuales, response.data.porcentaje)
          window.rezoFunctions.cerrarFormulario()
          document.getElementById("modal-gracias").style.display = "block"
        } else {
          alert("Error: " + response.data)
          $(".btn-enviar")
            .prop("disabled", false)
            .text(window.rezo_ajax.i18n.btn_enviar || "Agregar Rezos")
        }
      },
      error: () => {
        alert("Error de conexión. Inténtalo de nuevo.")
        $(".btn-enviar")
          .prop("disabled", false)
          .text(window.rezo_ajax.i18n.btn_enviar || "Agregar Rezos")
      },
    })
  },

  actualizarProgreso: (avemariasActuales, porcentaje) => {
    if (typeof window.jQuery === "undefined") return

    const $ = window.jQuery
    const avemariasElement = $(".avemarias")
    const porcentajeElement = $(".porcentaje")

    if (avemariasElement.length && porcentajeElement.length) {
      const textoActual = avemariasElement.text()
      const objetivo = textoActual.split(" / ")[1]
      avemariasElement.text(avemariasActuales.toLocaleString() + " / " + objetivo)
      porcentajeElement.text(porcentaje.toFixed(1) + "%")

      const path = $(".progress-ring-progress")
      if (path.length) {
        let length = 0
        const el = path[0]
        if (el.tagName.toLowerCase() === "circle") {
          const radius = parseFloat(el.getAttribute("r"))
          length = 2 * Math.PI * radius
        } else if (el.getTotalLength) {
          length = el.getTotalLength()
        }
        const offset = length - (porcentaje / 100) * length
        path.css("stroke-dashoffset", offset)
      }
    }
  },

  initProgressCircle: () => {
    if (typeof window.jQuery === "undefined") return

    const $ = window.jQuery
    const path = $(".progress-ring-progress")
    const progressContainer = $(".progress-circle")

    if (path.length === 0 || progressContainer.length === 0) {
      return
    }

    const porcentaje = progressContainer.data("porcentaje")
    let length = 0
    const el = path[0]
    if (el.tagName.toLowerCase() === "circle") {
      const radius = parseFloat(el.getAttribute("r"))
      length = 2 * Math.PI * radius
    } else if (el.getTotalLength) {
      length = el.getTotalLength()
    }

    path.css("stroke-dasharray", length)
    path.css("stroke-dashoffset", length)

    setTimeout(() => {
      const offset = length - (porcentaje / 100) * length
      path.css("stroke-dashoffset", offset)
    }, 500)
  },
}

// Exponer funciones individuales para compatibilidad
window.mostrarFormulario = window.rezoFunctions.mostrarFormulario
window.cerrarFormulario = window.rezoFunctions.cerrarFormulario
window.cerrarGracias = window.rezoFunctions.cerrarGracias

// Inicialización cuando jQuery esté listo
if (typeof window.jQuery !== "undefined") {
  window.jQuery(document).ready(($) => {
    // Event listeners
    $(document).on("click", ".btn-agregar-rezos", (e) => {
      e.preventDefault()
      window.rezoFunctions.mostrarFormulario()
    })

    $(document).on("click", ".opcion-cantidad", function () {
      $(".opcion-cantidad").removeClass("selected")
      $(this).addClass("selected")

      const cantidad = $(this).data("cantidad")

      if (cantidad === "otro") {
        $("#cantidad-personalizada").show()
        $("#cantidad-otro").focus()
      } else {
        $("#cantidad-personalizada").hide()
        window.rezoFunctions.validarFormulario()
      }
    })

    $(document).on("input", "#cantidad-otro", () => {
      window.rezoFunctions.validarFormulario()
    })

    $(document).on("input", "#captcha-respuesta", () => {
      window.rezoFunctions.validarFormulario()
    })

    $(document).on("submit", "#form-rezos", (e) => {
      e.preventDefault()
      window.rezoFunctions.enviarRezos()
    })

    // Inicializar círculo de progreso
    if ($(".progress-circle").length > 0) {
      window.rezoFunctions.initProgressCircle()
    }

    // Generar captcha inicial
    window.rezoFunctions.generarCaptcha()
  })
}
