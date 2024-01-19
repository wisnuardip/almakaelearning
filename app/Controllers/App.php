<?php

namespace App\Controllers;

use App\Models\AdminModel;
use App\Models\SiswaModel;
use App\Models\GuruModel;
use App\Models\MapelModel;
use App\Models\KelasModel;
use App\Models\GurukelasModel;
use App\Models\GurumapelModel;
use App\Models\SmtpModel;

class App extends BaseController
{
    protected $AdminModel;
    protected $SiswaModel;
    protected $GuruModel;
    protected $MapelModel;
    protected $KelasModel;
    protected $GurukelasModel;
    protected $GurumapelModel;
    protected $SmtpModel;

    public function __construct()
    {
        $validation = \Config\Services::validation();
        $this->AdminModel = new AdminModel();
        $this->SiswaModel = new SiswaModel();
        $this->GuruModel = new GuruModel();
        $this->MapelModel = new MapelModel();
        $this->KelasModel = new KelasModel();
        $this->GurukelasModel = new GurukelasModel();
        $this->GurumapelModel = new GurumapelModel();
        $this->SmtpModel = new SmtpModel();

        $this->email = \Config\Services::email();
    }

    public function index()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        // MENU DATA
        $data['dashboard'] = [
            'menu' => 'active',
            'expanded' => 'true'
        ];
        $data['master'] = [
            'menu' => '',
            'expanded' => 'false',
            'collapse' => ''
        ];
        $data['sub_master'] = [
            'siswa' => '',
            'guru' => ''
        ];
        $data['menu_kelas'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_mapel'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_relasi'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_profile'] = [
            'menu' => '',
            'expanded' => 'false',
        ];

        $data['guru'] = $this->GuruModel->asObject()->findAll();
        $data['guru_aktif'] = $this->GuruModel
            ->where('is_active', 1)
            ->get()->getResultObject();
        $data['guru_tidak_aktif'] = $this->GuruModel
            ->where('is_active', 0)
            ->get()->getResultObject();

        $data['siswa'] = $this->SiswaModel->asObject()->findAll();
        $data['siswa_aktif'] = $this->SiswaModel
            ->where('is_active', 1)
            ->get()->getResultObject();

        $data['siswa_tidak_aktif'] = $this->SiswaModel
            ->where('is_active', 0)
            ->get()->getResultObject();

        $data['kelas'] = $this->KelasModel->asObject()->findAll();
        $data['mapel'] = $this->MapelModel->asObject()->findAll();
        $data['admin'] = $this->AdminModel->asObject()->first();

        return view('admin/dashboard', $data);
    }

    // START::PROFILE & SETTING
    public function profile()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        // MENU DATA
        $data['dashboard'] = [
            'menu' => '',
            'expanded' => 'false'
        ];
        $data['master'] = [
            'menu' => '',
            'expanded' => 'false',
            'collapse' => ''
        ];
        $data['sub_master'] = [
            'siswa' => '',
            'guru' => ''
        ];
        $data['menu_kelas'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_mapel'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_relasi'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_profile'] = [
            'menu' => 'active',
            'expanded' => 'true',
        ];

        $data['admin'] = $this->AdminModel->asObject()->first();
        $data['smtp'] = $this->SmtpModel->asObject()->first();

        return view('admin/profile-setting', $data);
    }
    public function edit_profile()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $fileGambar = $this->request->getFile('avatar');

        // Cek Gambar, Apakah Tetap Gambar lama
        if ($fileGambar->getError() == 4) {
            $nama_gambar = $this->request->getVar('gambar_lama');
        } else {
            // Generate nama file Random
            $nama_gambar = $fileGambar->getRandomName();
            // Upload Gambar
            $fileGambar->move('assets/app-assets/user', $nama_gambar);
            // hapus File Yang Lama
            if ($this->request->getVar('gambar_lama') != 'default.jpg') {
                unlink('assets/app-assets/user/' . $this->request->getVar('gambar_lama'));
            }
        }

        $this->AdminModel->save([
            'id_admin' => session()->get('id'),
            'nama_admin' => $this->request->getVar('nama_admin'),
            'avatar' => $nama_gambar
        ]);

        session()->setFlashdata('pesan', "
            swal({
                title: 'Berhasil!',
                text: 'Profile telah diubah',
                type: 'success',
                padding: '2em'
            }); 
        ");
        return redirect()->to('app/profile');
    }
    public function edit_password()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $admin = $this->AdminModel->asObject()->find(session()->get('id'));

        if (password_verify($this->request->getVar('current_password'), $admin->password)) {
            $this->AdminModel->save([
                'id_admin' => $admin->id_admin,
                'password' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT)
            ]);
            session()->setFlashdata('pesan', "
                        swal({
                            title: 'Berhasil!',
                            text: 'Password telah diubah',
                            type: 'success',
                            padding: '2em'
                            });
                        ");
            return redirect()->to('app/profile');
        } else {
            session()->setFlashdata('pesan', "
                        swal({
                            title: 'Oops..',
                            text: 'Current Password Salah',
                            type: 'error',
                            padding: '2em'
                            });
                        ");
            return redirect()->to('app/profile');
        }
    }
    public function smtp_mail()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $this->SmtpModel->save([
            'id_mail' => $this->request->getVar('id_mail'),
            'smtp_host' => $this->request->getVar('smtp_host'),
            'smtp_user' => $this->request->getVar('smtp_user'),
            'smtp_pass' => $this->request->getVar('smtp_pass'),
            'smtp_port' => $this->request->getVar('smtp_port'),
            'smtp_crypto' => $this->request->getVar('smtp_crypto'),
        ]);

        session()->setFlashdata('pesan', "
                        swal({
                            title: 'Berhasil!',
                            text: 'SMTP email telah diubah',
                            type: 'success',
                            padding: '2em'
                            });
                        ");
        return redirect()->to('app/profile');
    }
    public function setting_email()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }

        $this->SmtpModel->save([
            'id_mail' => $this->request->getVar('id_mail'),
            'notif_akun' => $this->request->getVar('notif_akun'),
            'notif_materi' => $this->request->getVar('notif_materi'),
            'notif_tugas' => $this->request->getVar('notif_tugas'),
            'notif_ujian' => $this->request->getVar('notif_ujian'),
        ]);

        session()->setFlashdata('pesan', "
                        swal({
                            title: 'Berhasil!',
                            text: 'Notifikasi email telah diubah',
                            type: 'success',
                            padding: '2em'
                            });
                        ");
        return redirect()->to('app/profile');
    }
    // END::PROFILE & SETTING

    // START::KELAS
    public function kelas()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        // MENU DATA
        $data['dashboard'] = [
            'menu' => '',
            'expanded' => 'false'
        ];
        $data['master'] = [
            'menu' => '',
            'expanded' => 'false',
            'collapse' => ''
        ];
        $data['sub_master'] = [
            'siswa' => '',
            'guru' => ''
        ];
        $data['menu_kelas'] = [
            'menu' => 'active',
            'expanded' => 'true',
        ];
        $data['menu_mapel'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_relasi'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_profile'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        // END MENU DATA
        // ================================================

        // MASTER DATA
        $data['kelas'] = $this->KelasModel->asObject()->findAll();
        $data['admin'] = $this->AdminModel->asObject()->first();

        return view('admin/kelas/list', $data);
    }
    public function tambah_kelas()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        // Ambil data yang dikirim dari form
        $nama_kelas = $this->request->getVar('nama_kelas');
        $data_kelas = array();

        $index = 0; // Set index array awal dengan 0
        foreach ($nama_kelas as $nama) { // Kita buat perulangan berdasarkan nama_kelas sampai data terakhir
            array_push($data_kelas, array(
                'nama_kelas' => $nama,
            ));

            $index++;
        }

        $sql = $this->KelasModel->insertBatch($data_kelas);

        // Cek apakah query insert nya sukses atau gagal
        if ($sql) { // Jika sukses
            session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'data disimpan',
                    type: 'success',
                    padding: '2em'
                    })
                ");
            return redirect()->to('app/kelas');
        } else { // Jika gagal
            session()->setFlashdata('pesan', "
                swal({
                    title: 'Error!',
                    text: 'gagal disimpan',
                    type: 'error',
                    padding: '2em'
                    })
                ");
            return redirect()->to('app/kelas');
        }
    }
    public function edit_kelas()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        if ($this->request->isAJAX()) {
            $kelas = decrypt_url($this->request->getVar('id_kelas'));
            $data_kelas = $this->KelasModel->asObject()->find($kelas);
            echo json_encode($data_kelas);
        }
    }
    public function edit_kelas_()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $id_kelas = $this->request->getVar('id_kelas');
        $nama_kelas = $this->request->getVar('nama_kelas');

        $this->KelasModel->save([
            'id_kelas' => $id_kelas,
            'nama_kelas' => $nama_kelas
        ]);

        session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'nama kelas diubah',
                    type: 'success',
                    padding: '2em'
                    })
                ");
        return redirect()->to('app/kelas');
    }
    public function hapus_kelas($id = '')
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $id_kelas = decrypt_url($id);
        $this->KelasModel->delete($id_kelas);

        session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'data dihapus',
                    type: 'success',
                    padding: '2em'
                    })
                ");
        return redirect()->to('app/kelas');
    }
    // END::KELAS

    // START::mapel
    public function mapel()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        // MENU DATA
        $data['dashboard'] = [
            'menu' => '',
            'expanded' => 'false'
        ];
        $data['master'] = [
            'menu' => '',
            'expanded' => 'false',
            'collapse' => ''
        ];
        $data['sub_master'] = [
            'siswa' => '',
            'guru' => ''
        ];
        $data['menu_kelas'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_mapel'] = [
            'menu' => 'active',
            'expanded' => 'true',
        ];
        $data['menu_relasi'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_profile'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        // END MENU DATA
        // ================================================

        // MASTER DATA
        $data['mapel'] = $this->MapelModel->asObject()->findAll();
        $data['admin'] = $this->AdminModel->asObject()->first();
        return view('admin/mapel/list', $data);
    }
    public function tambah_mapel()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        // Ambil data yang dikirim dari form
        $nama_mapel = $this->request->getVar('nama_mapel');
        $data_mapel = array();

        $index = 0; // Set index array awal dengan 0
        foreach ($nama_mapel as $nama) { // Kita buat perulangan berdasarkan nama_mapel sampai data terakhir
            array_push($data_mapel, array(
                'nama_mapel' => $nama,
            ));

            $index++;
        }

        $sql = $this->MapelModel->insertBatch($data_mapel);

        // Cek apakah query insert nya sukses atau gagal
        if ($sql) { // Jika sukses
            session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'data disimpan',
                    type: 'success',
                    padding: '2em'
                    })
                ");
            return redirect()->to('app/mapel');
        } else { // Jika gagal
            session()->setFlashdata('pesan', "
                swal({
                    title: 'Error!',
                    text: 'gagal disimpan',
                    type: 'error',
                    padding: '2em'
                    })
                ");
            return redirect()->to('app/mapel');
        }
    }
    public function edit_mapel()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        if ($this->request->isAJAX()) {
            $mapel = decrypt_url($this->request->getVar('id_mapel'));
            $data_mapel = $this->MapelModel->asObject()->find($mapel);
            echo json_encode($data_mapel);
        }
    }
    public function edit_mapel_()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $id_mapel = $this->request->getVar('id_mapel');
        $nama_mapel = $this->request->getVar('nama_mapel');

        $this->MapelModel->save([
            'id_mapel' => $id_mapel,
            'nama_mapel' => $nama_mapel
        ]);

        session()->setFlashdata('pesan', "
            swal({
                title: 'Berhasil!',
                text: 'nama mapel diubah',
                type: 'success',
                padding: '2em'
                })
        ");
        return redirect()->to('app/mapel');
    }
    public function hapus_mapel($id = '')
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $id_mapel = decrypt_url($id);
        $this->MapelModel->delete($id_mapel);
        session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'data dihapus',
                    type: 'success',
                    padding: '2em'
                    })
                ");
        return redirect()->to('app/mapel');
    }
    // END::MAPEL

    // START::SISWA
    public function siswa()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        // MENU DATA
        $data['dashboard'] = [
            'menu' => '',
            'expanded' => 'false'
        ];
        $data['master'] = [
            'menu' => 'active',
            'expanded' => 'true',
            'collapse' => 'show'
        ];
        $data['sub_master'] = [
            'siswa' => 'active',
            'guru' => ''
        ];
        $data['menu_kelas'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_mapel'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_relasi'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_profile'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        // END MENU DATA
        // ================================================

        // MASTER DATA
        $data['siswa'] = $this->SiswaModel->getAll();
        $data['kelas'] = $this->KelasModel->asObject()->findAll();
        $data['admin'] = $this->AdminModel->asObject()->first();

        return view('admin/siswa/list', $data);
    }
    public function tambah_siswa()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $smtp = $this->SmtpModel->asObject()->first();
        // Ambil data yang dikirim dari form
        $nama_siswa = $this->request->getVar('nama_siswa');
        $data_siswa = array();

        $index = 0; // Set index array awal dengan 0
        foreach ($nama_siswa as $nama) { // Kita buat perulangan berdasarkan nama_siswa sampai data terakhir
            $kelas = $this->KelasModel->asObject()->find($this->request->getVar('kelas')[$index]);
            array_push($data_siswa, array(
                'no_induk_siswa' => $this->request->getVar('nis')[$index],
                'nama_siswa' => $nama,
                'email' => $this->request->getVar('email')[$index],
                'password' => password_hash($this->request->getVar('nis')[$index], PASSWORD_DEFAULT),
                'jenis_kelamin' => $this->request->getVar('jenis_kelamin')[$index],
                'kelas' => $this->request->getVar('kelas')[$index],
                'role' => 2,
                'is_active' => 1,
                'date_created' => time(),
                'avatar' => 'default.jpg'
            ));

            // KIRIM EMAIL
            if ($smtp->notif_akun == 1) {
                $config['SMTPHost'] = $smtp->smtp_host;
                $config['SMTPUser'] = $smtp->smtp_user;
                $config['SMTPPass'] = $smtp->smtp_pass;
                $config['SMTPPort'] = $smtp->smtp_port;
                $config['SMTPCrypto'] = $smtp->smtp_crypto;
                $config['mailType'] = 'html';

                $this->email->initialize($config);

                $this->email->setNewline("\r\n");

                $this->email->setFrom($smtp->smtp_user, 'SMAIT ALMAKA');
                $this->email->setTo($this->request->getVar('email')[$index]);

                $this->email->setSubject('Akun SMAIT ALMAKA');
                $this->email->setMessage('
                    <div style="color: #000; padding: 10px;">
                        <div
                            style="font-family: `Segoe UI`, Tahoma, Geneva, Verdana, sans-serif; font-size: 20px; color: #1C3FAA; font-weight: bold;">
                            SMAIT ALMAKA</div>
                        <small style="color: #000;">by SMAIT ALMAKA</small>
                        <br>
                        <p style="font-family: `Segoe UI`, Tahoma, Geneva, Verdana, sans-serif; color: #000;">Hallo ' . $nama . ' <br>
                            <span style="color: #000;">Admin telah menambahkan anda kedalam aplikasi SMAIT ALMAKA</span></p>
                        <table style="font-family: `Segoe UI`, Tahoma, Geneva, Verdana, sans-serif; color: #000;">
                            <tr>
                                <td>NAMA</td>
                                <td> : ' . $nama . '</td>
                            </tr>
                            <tr>
                                <td>EMAIL</td>
                                <td> : ' . $this->request->getVar('email')[$index] . '</td>
                            </tr>
                            <tr>
                                <td>KELAS</td>
                                <td> : ' . $kelas->nama_kelas . '</td>
                            </tr>
                            <tr>
                                <td>PASSWORD</td>
                                <td> : ' . $this->request->getVar('nis')[$index] . '</td>
                            </tr>
                        </table>
                        <br>
                        <a href="' . base_url('auth') . '"
                            style="display: inline-block; width: 100px; height: 30px; background: #1C3FAA; color: #fff; text-decoration: none; border-radius: 5px; text-align: center; line-height: 30px; font-family: `Segoe UI`, Tahoma, Geneva, Verdana, sans-serif;">Sign
                            In
                            Now!
                        </a>
                    </div>
                ');

                if (!$this->email->send()) {
                    echo $this->email->printDebugger();
                    die();
                }
            }

            $index++;
        }

        // dd($data_siswa);

        $this->SiswaModel->insertBatch($data_siswa);

        session()->setFlashdata('pesan', "
            swal({
                title: 'Berhasil!',
                text: 'data disimpan',
                type: 'success',
                padding: '2em'
                })
            ");
        return redirect()->to('app/siswa');
    }
    public function edit_siswa()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        if ($this->request->isAJAX()) {
            $siswa = decrypt_url($this->request->getVar('id_siswa'));
            $data_siswa = $this->SiswaModel->asObject()->find($siswa);
            echo json_encode($data_siswa);
        }
    }
    public function edit_siswa_()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $id_siswa = $this->request->getVar('id_siswa');
        $email = $this->request->getVar('email');
        $nama_siswa = $this->request->getVar('nama_siswa');
        $kelas = $this->request->getVar('kelas');
        $active = $this->request->getVar('active');

        $this->SiswaModel
            ->where('id_siswa', $id_siswa)
            ->set('nama_siswa', $nama_siswa)
            ->set('email', $email)
            ->set('kelas', $kelas)
            ->set('is_active', $active)
            ->update();

        session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'data siswa diubah',
                    type: 'success',
                    padding: '2em'
                    })
                ");
        return redirect()->to('app/siswa');
    }
    public function hapus_siswa($id = '')
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $id_siswa = decrypt_url($id);
        $this->SiswaModel->delete($id_siswa);
        session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'data dihapus',
                    type: 'success',
                    padding: '2em'
                    })
                ");
        return redirect()->to('app/siswa');
    }
    // END::SISWA

    // START::GURU
    public function guru()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        // MENU DATA
        $data['dashboard'] = [
            'menu' => '',
            'expanded' => 'false'
        ];
        $data['master'] = [
            'menu' => 'active',
            'expanded' => 'true',
            'collapse' => 'show'
        ];
        $data['sub_master'] = [
            'siswa' => '',
            'guru' => 'active'
        ];
        $data['menu_kelas'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_mapel'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_relasi'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_profile'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        // END MENU DATA
        // ================================================

        // MASTER DATA
        $data['guru'] = $this->GuruModel->asObject()->findAll();
        $data['admin'] = $this->AdminModel->asObject()->first();
        return view('admin/guru/list', $data);
    }
    public function tambah_guru()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $smtp = $this->SmtpModel->asObject()->first();
        // Ambil data yang dikirim dari form
        $nama_guru = $this->request->getVar('nama_guru');
        $data_guru = array();

        $index = 0; // Set index array awal dengan 0
        foreach ($nama_guru as $nama) { // Kita buat perulangan berdasarkan nama_guru sampai data terakhir
            $pwd_guru = 123;
            array_push($data_guru, array(
                'nama_guru' => $nama,
                'email' => $this->request->getVar('email')[$index],
                'password' => password_hash($pwd_guru, PASSWORD_DEFAULT),
                'role' => 3,
                'is_active' => 1,
                'date_created' => time(),
                'avatar' => 'default.jpg'
            ));

            // KIRIM EMAIL
            if ($smtp->notif_akun == 1) {
                $config['SMTPHost'] = $smtp->smtp_host;
                $config['SMTPUser'] = $smtp->smtp_user;
                $config['SMTPPass'] = $smtp->smtp_pass;
                $config['SMTPPort'] = $smtp->smtp_port;
                $config['SMTPCrypto'] = $smtp->smtp_crypto;
                $config['mailType'] = 'html';

                $this->email->initialize($config);

                $this->email->setNewline("\r\n");

                $this->email->setFrom($smtp->smtp_user, 'SMAIT ALMAKA');
                $this->email->setTo($this->request->getVar('email')[$index]);

                $this->email->setSubject('Akun SMAIT ALMAKA');
                $this->email->setMessage('
                    <div style="color: #000; padding: 10px;">
                        <div
                            style="font-family: `Segoe UI`, Tahoma, Geneva, Verdana, sans-serif; font-size: 20px; color: #1C3FAA; font-weight: bold;">
                            SMAIT ALMAKA</div>
                        <small style="color: #000;">by SMAIT ALMAKA</small>
                        <br>
                        <p style="font-family: `Segoe UI`, Tahoma, Geneva, Verdana, sans-serif; color: #000;">Hallo ' . $nama . ' <br>
                            <span style="color: #000;">Admin telah menambahkan anda kedalam aplikasi SMAIT ALMAKA</span></p>
                        <table style="font-family: `Segoe UI`, Tahoma, Geneva, Verdana, sans-serif; color: #000;">
                            <tr>
                                <td>NAMA</td>
                                <td style="text-transform: uppercase;"> : ' . $nama . '</td>
                            </tr>
                            <tr>
                                <td>EMAIL</td>
                                <td> : ' . $this->request->getVar('email')[$index] . '</td>
                            </tr>
                            <tr>
                                <td>ROLE</td>
                                <td> : GURU</td>
                            </tr>
                            <tr>
                                <td>PASSWORD</td>
                                <td> : ' . $pwd_guru . '</td>
                            </tr>
                            <tr>
                                <td>STATUS AKUN</td>
                                <td> : AKTIF</td>
                            </tr>
                        </table>
                        <br>
                        <a href="' . base_url('auth') . '"
                            style="display: inline-block; width: 100px; height: 30px; background: #1C3FAA; color: #fff; text-decoration: none; border-radius: 5px; text-align: center; line-height: 30px; font-family: `Segoe UI`, Tahoma, Geneva, Verdana, sans-serif;">Sign
                            In
                            Now!
                        </a>
                    </div>
                ');

                if (!$this->email->send()) {
                    echo $this->email->printDebugger();
                    die();
                }
            }

            $index++;
        }

        $sql = $this->GuruModel->insertBatch($data_guru);

        // Cek apakah query insert nya sukses atau gagal
        if ($sql) { // Jika sukses
            session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'data disimpan',
                    type: 'success',
                    padding: '2em'
                    })
                ");
            return redirect()->to('app/guru');
        } else { // Jika gagal
            session()->setFlashdata('pesan', "
                swal({
                    title: 'Error!',
                    text: 'gagal disimpan',
                    type: 'error',
                    padding: '2em'
                    })
                ");
            return redirect()->to('app/guru');
        }
    }
    public function edit_guru()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        if ($this->request->isAJAX()) {
            $guru = decrypt_url($this->request->getVar('id_guru'));
            $data_guru = $this->GuruModel->asObject()->find($guru);
            echo json_encode($data_guru);
        }
    }
    public function edit_guru_()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $id_guru = $this->request->getVar('id_guru');
        $nama_guru = $this->request->getVar('nama_guru');
        $email = $this->request->getVar('email');
        $active = $this->request->getVar('active');

        $this->GuruModel->save([
            'id_guru' => $id_guru,
            'nama_guru' => $nama_guru,
            'email' => $email,
            'is_active' => $active
        ]);

        session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'data guru diubah',
                    type: 'success',
                    padding: '2em'
                    })
                ");
        return redirect()->to('app/guru');
    }
    public function hapus_guru($id = '')
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        $id_guru = decrypt_url($id);
        $this->GuruModel->delete($id_guru);
        session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'data dihapus',
                    type: 'success',
                    padding: '2em'
                    })
                ");
        return redirect()->to('app/guru');
    }
    // END::GURU

    // START::RELASI
    public function relasi()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        // MENU DATA
        $data['dashboard'] = [
            'menu' => '',
            'expanded' => 'false'
        ];
        $data['master'] = [
            'menu' => '',
            'expanded' => 'false',
            'collapse' => ''
        ];
        $data['sub_master'] = [
            'siswa' => '',
            'guru' => ''
        ];
        $data['menu_kelas'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_mapel'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_relasi'] = [
            'menu' => 'active',
            'expanded' => 'true',
        ];
        $data['menu_profile'] = [
            'menu' => '',
            'expanded' => 'false',
        ];

        $data['kelas'] = $this->KelasModel->asObject()->findAll();
        $data['mapel'] = $this->MapelModel->asObject()->findAll();
        $data['guru'] = $this->GuruModel->asObject()->findAll();
        $data['admin'] = $this->AdminModel->asObject()->first();

        return view('admin/guru/list-relasi', $data);
    }
    public function atur_relasi($id = '')
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        // MENU DATA
        $data['dashboard'] = [
            'menu' => '',
            'expanded' => 'false'
        ];
        $data['master'] = [
            'menu' => '',
            'expanded' => 'false',
            'collapse' => ''
        ];
        $data['sub_master'] = [
            'siswa' => '',
            'guru' => ''
        ];
        $data['menu_kelas'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_mapel'] = [
            'menu' => '',
            'expanded' => 'false',
        ];
        $data['menu_relasi'] = [
            'menu' => 'active',
            'expanded' => 'true',
        ];
        $data['menu_profile'] = [
            'menu' => '',
            'expanded' => 'false',
        ];

        $id_guru = decrypt_url($id);
        $data['kelas'] = $this->KelasModel->asObject()->findAll();
        $data['mapel'] = $this->MapelModel->asObject()->findAll();
        $data['guru'] = $this->GuruModel->asObject()->find($id_guru);
        $data['admin'] = $this->AdminModel->asObject()->first();

        return view('admin/guru/relasi', $data);
    }
    public function guru_kelas()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        if ($this->request->isAJAX()) {
            $id_guru = decrypt_url($this->request->getVar('id_guru'));
            $id_kelas = $this->request->getVar('id_kelas');

            $kelass = $this->KelasModel->find($id_kelas);
            $kelas = $kelass['nama_kelas'];


            $data = [
                'guru' => $id_guru,
                'kelas' => $id_kelas,
                'nama_kelas' => $kelas
            ];

            $result = $this->GurukelasModel->getALLByGuruAndKelas($id_guru, $id_kelas);

            if (count($result) < 1) {
                $this->GurukelasModel->save($data);
            } else {
                $this->GurukelasModel
                    ->where('guru', $id_guru)
                    ->where('kelas', $id_kelas)
                    ->delete();
            }

            session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'data diubah',
                    type: 'success',
                    padding: '2em'
                    })
                ");
        }
    }
    public function guru_mapel()
    {
        if (session()->get('role') != 1) {
            return redirect()->to('auth');
        }
        if ($this->request->isAJAX()) {
            $id_guru = decrypt_url($this->request->getVar('id_guru'));
            $id_mapel = $this->request->getVar('id_mapel');

            $mapels = $this->MapelModel->find($id_mapel);
            $mapel = $mapels['nama_mapel'];


            $data = [
                'guru' => $id_guru,
                'mapel' => $id_mapel,
                'nama_mapel' => $mapel
            ];

            $result = $this->GurumapelModel->getALLByGuruAndMapel($id_guru, $id_mapel);

            if (count($result) < 1) {
                $this->GurumapelModel->save($data);
            } else {
                $this->GurumapelModel
                    ->where('guru', $id_guru)
                    ->where('mapel', $id_mapel)
                    ->delete();
            }

            session()->setFlashdata('pesan', "
                swal({
                    title: 'Berhasil!',
                    text: 'data diubah',
                    type: 'success',
                    padding: '2em'
                    })
                ");
        }
    }
    // END::RELASI
}
