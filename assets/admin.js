const $ = window.jQuery // Declare the jQuery variable

jQuery(document).ready(($) => {
  // Funcionalidad para editar intenciones
  $(".edit-intencion").click(function () {
    const id = $(this).data("id")
    // Aquí puedes agregar la funcionalidad para editar
    alert("Funcionalidad de edición en desarrollo. ID: " + id)
  })
})
