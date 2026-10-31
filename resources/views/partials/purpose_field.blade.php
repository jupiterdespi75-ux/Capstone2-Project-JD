<div class="mb-3">
  <label class="form-label">Purpose of Visit</label>
  <select class="form-select" id="purposeSelect">
    <option value="">Select purpose of visit&hellip;</option>
    <option>General inquiry</option>
    <option>School information inquiry</option>
    <option>Campus visit</option>
    <option>Appointment with school personnel</option>
    <option>Meeting with faculty/staff</option>
    <option>Document processing</option>
    <option>School-related transaction</option>
    <option>Official business</option>
    <option>School event attendance</option>
    <option>Visitor / guest</option>
    <option value="Other (please specify)">Other (please specify)</option>
  </select>
  <input type="text" class="form-control mt-2 d-none" id="purposeOther" placeholder="Please specify...">
  <!-- Actual submitted value: mirrors the select, or "Other (please specify): <detail>" -->
  <input type="hidden" name="purpose" id="purposeHidden">
</div>

<script>
(function () {
  const select = document.getElementById('purposeSelect');
  const other = document.getElementById('purposeOther');
  const hidden = document.getElementById('purposeHidden');

  function sync() {
    if (select.value === 'Other (please specify)') {
      other.classList.remove('d-none');
      hidden.value = other.value ? `Other (please specify): ${other.value}` : '';
    } else {
      other.classList.add('d-none');
      hidden.value = select.value;
    }
  }

  select.addEventListener('change', sync);
  other.addEventListener('input', sync);
  sync();
})();
</script>
