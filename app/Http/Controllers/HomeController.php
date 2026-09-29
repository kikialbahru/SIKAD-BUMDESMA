<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function page(string $page)
    {
        $page = ['tentang' => 'profil', 'profil-lkd' => 'profil'][$page] ?? $page;
        $pageContent = collect($this->defaults()['page'])->firstWhere('slug', $page);

        abort_unless($pageContent, 404);

        return view('content', ['page' => $pageContent, 'pageKey' => $page]);
    }

    private function defaults(): array
    {
        return [
            'slide' => [
                ['judul' => 'Bersama membangun desa, menuju kemandirian.', 'ringkasan' => 'Mengenal lebih dekat program, kegiatan, dan potensi lokal BUMDESMA LKD TARUB untuk ekonomi masyarakat yang tumbuh berkelanjutan.', 'gambar_url' => 'https://images.unsplash.com/photo-1500076656116-558758c991c1?auto=format&fit=crop&w=2200&q=90'],
                ['judul' => 'Bergerak bersama, berdampak nyata.', 'ringkasan' => 'Setiap pelatihan, pertemuan, dan kolaborasi menjadi langkah untuk memperkuat usaha masyarakat.', 'gambar_url' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=2200&q=90'],
                ['judul' => 'Dari karya warga, lahir masa depan.', 'ringkasan' => 'Mengenalkan produk unggulan dan potensi lokal sebagai bagian dari ekonomi desa yang berdaya.', 'gambar_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=2200&q=90'],
            ],
            'program' => [
                ['judul' => 'Pemberdayaan masyarakat', 'ringkasan' => 'Mendukung peningkatan kemampuan dan potensi masyarakat agar semakin mandiri.'],
                ['judul' => 'Pengembangan ekonomi desa', 'ringkasan' => 'Mendorong tumbuhnya usaha dan ekonomi lokal melalui peluang yang nyata.'],
                ['judul' => 'Kerja sama & kemitraan', 'ringkasan' => 'Membangun kolaborasi yang saling menguatkan dengan berbagai pihak.'],
                ['judul' => 'Pengembangan potensi lokal', 'ringkasan' => 'Mempromosikan kekayaan wilayah dan karya usaha masyarakat.'],
            ],
            'activity' => [['judul' => 'Pelatihan Pengembangan Usaha Masyarakat', 'ringkasan' => 'BUMDESMA LKD TARUB terus mendorong peningkatan kapasitas dan pengembangan potensi ekonomi masyarakat.', 'gambar_url' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1400&q=85']],
            'product' => [['judul' => 'Hasil bumi Tarub', 'ringkasan' => 'Produk unggulan dari tanah sendiri.', 'gambar_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=85'], ['judul' => 'Karya usaha warga', 'ringkasan' => 'Karya UMKM lokal Tarub.', 'gambar_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=900&q=85']],
            'news' => [['judul' => 'Membuka ruang baru untuk kolaborasi masyarakat', 'ringkasan' => 'Informasi terbaru BUMDESMA LKD TARUB.']],
            'gallery' => [['judul' => 'Kebersamaan masyarakat', 'gambar_url' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1000&q=85']],
            'page' => [
                ['slug' => 'profil', 'label' => 'Tentang BUMDESMA', 'judul' => 'Tumbuh dari desa, untuk desa.', 'intro' => 'Mengenal peran, arah, dan semangat BUMDESMA LKD TARUB dalam mendukung kemandirian masyarakat.', 'gambar_url' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'kegiatan', 'label' => 'Cerita dari lapangan', 'judul' => 'Kegiatan yang menggerakkan.', 'intro' => 'Dokumentasi kegiatan, pelatihan, dan kolaborasi yang memperkuat ekonomi masyarakat desa.', 'gambar_url' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'program', 'label' => 'Yang kami kerjakan', 'judul' => 'Program unggulan BUMDESMA.', 'intro' => 'Inisiatif yang dirancang untuk membuka peluang, menumbuhkan kemampuan, dan menguatkan desa.', 'gambar_url' => 'https://images.unsplash.com/photo-1556761175-4b46a572b786?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'potensi', 'label' => 'Dari tanah sendiri', 'judul' => 'Potensi lokal, cerita kita.', 'intro' => 'Mengenalkan produk, UMKM, hasil bumi, dan kekayaan wilayah Tarub kepada lebih banyak orang.', 'gambar_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'berita', 'label' => 'Tetap terhubung', 'judul' => 'Informasi terkini dari Tarub.', 'intro' => 'Berita, pengumuman, dan kabar terbaru seputar kegiatan serta perkembangan BUMDESMA.', 'gambar_url' => 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'galeri', 'label' => 'Momen yang berarti', 'judul' => 'Jejak langkah kebersamaan.', 'intro' => 'Kumpulan dokumentasi kegiatan lapangan, pelatihan, pertemuan, dan kerja sama desa.', 'gambar_url' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'kontak', 'label' => 'Mari berbincang', 'judul' => 'Mari bergerak bersama.', 'intro' => 'Hubungi kami untuk informasi, kolaborasi, atau berbagi cerita tentang potensi desa.', 'gambar_url' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1800&q=85'],
            ],
        ];
    }
}
