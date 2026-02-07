document.addEventListener("DOMContentLoaded", function () {
  console.log("💳 Transaksi Staff initialized (SAFE MODE)");

  const modalForm = document.getElementById("modalForm");
  const modalHapus = document.getElementById("modalHapus");
  const btnTambah = document.getElementById("btnTambahTransaksi");
  const formTransaksi = document.getElementById("formTransaksi");
  const btnBatal = document.getElementById("btnBatal");
  const modalTitle = document.getElementById("modalTitle");

  let currentDeleteId = null;

  // =========================
  // TAMBAH TRANSAKSI
  // =========================
  btnTambah?.addEventListener("click", () => {
    modalTitle.textContent = "Tambah Transaksi";
    formTransaksi.reset();
    document.getElementById("id").value = "";
    modalForm.classList.add("active");
    document.body.style.overflow = "hidden";
  });

  // =========================
  // BATAL FORM
  // =========================
  btnBatal?.addEventListener("click", () => {
    modalForm.classList.remove("active");
    document.body.style.overflow = "auto";
  });

  modalForm?.addEventListener("click", (e) => {
    if (e.target === modalForm) {
      modalForm.classList.remove("active");
      document.body.style.overflow = "auto";
    }
  });

  // =========================
  // SUBMIT FORM (ANTI DOUBLE)
  // =========================
  formTransaksi?.addEventListener("submit", function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    const fd = new FormData(formTransaksi);
    const id = document.getElementById("id").value;
    const url = id ? "update_transaksi.php" : "tambah_transaksi.php";

    fetch(url, { method: "POST", body: fd })
      .then((res) => res.text())
      .then((data) => {
        if (data.trim() === "success") {
          alert(
            id
              ? "Transaksi berhasil diupdate!"
              : "Transaksi berhasil ditambahkan!"
          );
          location.reload();
        } else {
          alert("Error: " + data);
        }
      });
  });

  // =========================
  // EDIT TRANSAKSI (FIXED)
  // =========================
  document.querySelectorAll(".btn-edit").forEach((btn) => {
    btn.addEventListener("click", function () {
      const row = this.closest("tr");
      if (!row) return;

      const id = row.dataset.id;
      const id_obat = row.dataset.idObat;

      const cells = row.querySelectorAll("td");
      if (cells.length < 5) return;

      const jenisText = cells[2].textContent.toLowerCase();
      const jenis = jenisText.includes("masuk") ? "masuk" : "keluar";

      modalTitle.textContent = "Edit Transaksi";
      document.getElementById("id").value = id;
      document.getElementById("id_obat").value = id_obat;
      document.getElementById("jenis").value = jenis;
      document.getElementById("jumlah").value = cells[3].textContent.trim();
      document.getElementById("keterangan").value = cells[4].textContent.trim();

      modalForm.classList.add("active");
      document.body.style.overflow = "hidden";
    });
  });

  // =========================
  // DELETE TRANSAKSI
  // =========================
  document.querySelectorAll(".btn-delete").forEach((btn) => {
    btn.addEventListener("click", () => {
      currentDeleteId = btn.closest("tr").dataset.id;
      modalHapus.classList.add("active");
      document.body.style.overflow = "hidden";
    });
  });

  document.getElementById("confirmHapus")?.addEventListener("click", () => {
    if (!currentDeleteId) return;

    const fd = new FormData();
    fd.append("id", currentDeleteId);

    fetch("hapus_transaksi.php", { method: "POST", body: fd })
      .then((res) => res.text())
      .then((data) => {
        if (data.trim() === "success") {
          alert("Transaksi berhasil dihapus!");
          location.reload();
        } else {
          alert("Error: " + data);
        }
      });
  });

  document.getElementById("cancelHapus")?.addEventListener("click", () => {
    modalHapus.classList.remove("active");
    document.body.style.overflow = "auto";
    currentDeleteId = null;
  });

  console.log("✅ Transaksi Staff READY (EDIT WORKING)");
});
