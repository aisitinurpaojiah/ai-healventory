document.addEventListener("DOMContentLoaded", function () {
  console.log("💊 Kelola Obat script initialized (SAFE MODE)");

  const modalForm = document.getElementById("modalForm");
  const modalHapus = document.getElementById("modalHapus");
  const btnTambah = document.getElementById("btnTambah");
  const formObat = document.getElementById("formObat");
  const btnBatal = document.getElementById("btnBatal");
  const modalTitle = document.getElementById("modalTitle");

  let currentDeleteId = null;

  // =========================
  // TAMBAH OBAT
  // =========================
  btnTambah?.addEventListener("click", () => {
    modalTitle.textContent = "Tambah Obat";
    formObat.reset();
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
  formObat?.addEventListener("submit", function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    const formData = new FormData(formObat);
    const id = document.getElementById("id").value;
    const url = id ? "update_obat.php" : "tambah_obat.php";

    fetch(url, { method: "POST", body: formData })
      .then((res) => res.text())
      .then((data) => {
        if (data.trim() === "success") {
          alert(id ? "Obat berhasil diupdate!" : "Obat berhasil ditambahkan!");
          location.reload();
        } else {
          alert("Error: " + data);
        }
      })
      .catch((err) => alert("Error: " + err));
  });

  // =========================
  // EDIT
  // =========================
  document.querySelectorAll(".btn-edit").forEach((btn) => {
    btn.addEventListener("click", () => {
      const row = btn.closest("tr");
      const cells = row.querySelectorAll("td");

      modalTitle.textContent = "Edit Obat";
      document.getElementById("id").value = row.dataset.id;
      document.getElementById("kode_obat").value = cells[0].textContent;
      document.getElementById("nama").value = cells[1].textContent;
      document.getElementById("kategori").value = cells[2].textContent;
      document.getElementById("stok_awal").value = cells[3].textContent;
      document.getElementById("stok_minimum").value = cells[4].textContent;
      document.getElementById("tgl_kadaluarsa").value = cells[5].textContent;

      modalForm.classList.add("active");
      document.body.style.overflow = "hidden";
    });
  });

  // =========================
  // DELETE
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

    fetch("hapus_obat.php", { method: "POST", body: fd })
      .then((res) => res.text())
      .then((data) => {
        if (data.trim() === "success") {
          alert("Obat berhasil dihapus!");
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

  console.log("✅ Kelola Obat READY (NO DUPLICATE)");
});
