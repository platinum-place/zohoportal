<div class="modal fade" id="cotizar_vida" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Cotización de Plan Vida</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= site_url("cotizaciones") ?>">
        <input type="text" hidden value="vida" name="cotizacion">
        <div class="modal-body">
          <div class="mb-3">
            <label for="deudor" class="form-label">Fecha de Nacimiento Deudor</label>
            <input type="date" class="form-control" id="deudor" required name="deudor">
          </div>
          <div class="mb-3">
            <label for="codeudor" class="form-label">Fecha de Nacimiento Codeudor</label>
            <input type="date" class="form-control" id="codeudor" name="codeudor">
          </div>
          <div class="mb-3">
            <label for="plazo" class="form-label">Plazo</label>
            <input type="number" class="form-control" name="plazo" required>
          </div>
          <div class="mb-3">
            <label for="suma" class="form-label">Suma Asegurada</label>
            <input type="number" class="form-control" required name="suma">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Cotizar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Librerias adicionales -->

<!-- CSS personalizado -->
<?= $this->section('css') ?>
<!-- hace que el rango de clic del campo de fecha sea mas grande -->
<style>
  #deudor::-webkit-calendar-picker-indicator {
    padding-left: 50%;
  }

  #codeudor::-webkit-calendar-picker-indicator {
    padding-left: 50%;
  }
</style>
<?= $this->endSection() ?>