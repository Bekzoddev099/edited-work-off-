<script>
    var confirmModal = document.getElementById('confirmModal');
    confirmModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var modalInput = document.getElementById('workedOffId');
        modalInput.value = id;
    });
</script>