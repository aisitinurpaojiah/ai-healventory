<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../../config/database.php';

// BAGIAN AKSI CRUD (AJAX Handler)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
        // TAMBAH USER
        if ($action === 'tambah') {
            $fullname = trim($_POST['fullname']);
            $role = trim($_POST['role']);
            $username = trim($_POST['username']);
            $password = $_POST['password'];

            // Validasi
            if (empty($fullname) || empty($role) || empty($username) || empty($password)) {
                echo json_encode(['status' => 'error', 'msg' => 'Semua field harus diisi']);
                exit;
            }

            // Cek username sudah ada
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['status' => 'error', 'msg' => 'Username sudah digunakan']);
                exit;
            }

            $password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (fullname, role, username, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fullname, $role, $username, $password]);
            echo json_encode(['status' => 'success', 'msg' => 'Pengguna berhasil ditambahkan']);
            exit;
        }

        // UPDATE USER
        if ($action === 'update') {
            $id = (int)$_POST['id'];
            $fullname = trim($_POST['fullname']);
            $role = trim($_POST['role']);
            $username = trim($_POST['username']);
            $password = $_POST['password'] ?? '';

            // Validasi
            if (empty($fullname) || empty($role) || empty($username)) {
                echo json_encode(['status' => 'error', 'msg' => 'Nama, role, dan username harus diisi']);
                exit;
            }

            // Cek username conflict (kecuali untuk user yg sama)
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $check->execute([$username, $id]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['status' => 'error', 'msg' => 'Username sudah digunakan']);
                exit;
            }

            if (!empty($password)) {
                $password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, role=?, username=?, password=? WHERE id=?");
                $stmt->execute([$fullname, $role, $username, $password, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET fullname=?, role=?, username=? WHERE id=?");
                $stmt->execute([$fullname, $role, $username, $id]);
            }
            echo json_encode(['status' => 'success', 'msg' => 'Pengguna berhasil diperbarui']);
            exit;
        }

        // HAPUS USER
        if ($action === 'hapus') {
            $id = (int)$_POST['id'];

            // Cek apakah user yang akan dihapus adalah diri sendiri
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['status' => 'error', 'msg' => 'Tidak dapat menghapus akun sendiri']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'msg' => 'Pengguna berhasil dihapus']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        exit;
    }
}

// BAGIAN TAMPILAN
include '../includes/header.php';
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC");
?>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <span class="role">Super Admin</span>
            <i class="bi bi-person-circle profile-icon"></i>
        </header>

        <section class="table-section">
            <div class="table-header">
                <h2>Kelola User</h2>
                <button class="btn-primary" id="btnTambahUser">+ Tambah Pengguna</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Peran</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $users->fetch()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($row['role']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td>••••••••</td>
                            <td>
                                <button type="button" class="btn-edit"
                                    data-id="<?= $row['id'] ?>"
                                    data-fullname="<?= htmlspecialchars($row['fullname']) ?>"
                                    data-role="<?= htmlspecialchars($row['role']) ?>"
                                    data-username="<?= htmlspecialchars($row['username']) ?>">
                                    ✏️
                                </button>
                                <button type="button" class="btn-delete" data-id="<?= $row['id'] ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<!-- MODAL POPUP -->
<div class="modal-blur" id="userModal" style="display: none;">
    <div class="modal-content">
        <h3 id="modalTitle">Tambah Pengguna</h3>
        <form id="userForm">
            <input type="hidden" name="id" id="userId">
            <input type="hidden" name="action" id="formAction" value="tambah">

            <label>Nama Lengkap</label>
            <input type="text" name="fullname" id="fullname" required>

            <label>Peran</label>
            <select name="role" id="role" required>
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="staff">Staff</option>
            </select>

            <label>Username</label>
            <input type="text" name="username" id="username" required>

            <label>Password <span id="passwordHint">(Kosongkan jika tidak ingin mengubah)</span></label>
            <input type="password" name="password" id="password">

            <div class="form-actions">
                <button type="submit" class="btn-primary">Simpan</button>
                <button type="button" class="btn-outline" id="closeModal">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Ambil elemen modal
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    const modalTitle = document.getElementById('modalTitle');
    const btnTambah = document.getElementById('btnTambahUser');
    const btnClose = document.getElementById('closeModal');
    const passwordField = document.getElementById('password');
    const passwordHint = document.getElementById('passwordHint');

    // Tombol Tambah User
    btnTambah.addEventListener('click', function() {
        modalTitle.textContent = 'Tambah Pengguna';
        form.reset();
        document.getElementById('userId').value = '';
        document.getElementById('formAction').value = 'tambah';
        passwordField.required = true;
        passwordHint.style.display = 'none';
        modal.style.display = 'flex';
    });

    // Tombol Edit
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const fullname = this.getAttribute('data-fullname');
            const role = this.getAttribute('data-role');
            const username = this.getAttribute('data-username');

            modalTitle.textContent = 'Edit Pengguna';
            document.getElementById('userId').value = id;
            document.getElementById('fullname').value = fullname;
            document.getElementById('role').value = role;
            document.getElementById('username').value = username;
            document.getElementById('password').value = '';
            document.getElementById('formAction').value = 'update';
            passwordField.required = false;
            passwordHint.style.display = 'inline';
            modal.style.display = 'flex';
        });
    });

    // Tombol Delete
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');

            if (!confirm('Yakin ingin menghapus pengguna ini?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'hapus');
            formData.append('id', id);

            fetch('kelola_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.msg);
                        location.reload();
                    } else {
                        alert('Error: ' + data.msg);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan saat menghapus data');
                });
        });
    });

    // Submit Form (Tambah/Update)
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch('kelola_user.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.msg);
                    location.reload();
                } else {
                    alert('Error: ' + data.msg);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan saat menyimpan data');
            });
    });

    // Tutup Modal
    btnClose.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // Klik di luar modal untuk menutup
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
</script>

<?php include '../includes/footer.php'; ?>