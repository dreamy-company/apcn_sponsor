PRODUCT REQUIREMENTS DOCUMENT (PRD)
Project Name: J4U Sponsorship Deal Management System (APCN 2027)
Document Version: 1.0
Status: Approved for Development
1. Product Overview
Sistem Manajemen Sponsorship J4U adalah aplikasi web internal yang berfungsi sebagai Single Source of Truth (SSOT) untuk seluruh kesepakatan (deals) sponsor pada event APCN 2027. Sistem ini mendigitalisasi proses yang sebelumnya manual menjadi alur yang terpusat, mulai dari pendataan paket sponsor, kustomisasi deal oleh panitia (Tim J4U) berdasarkan lobi dokter, pelacakan termin pembayaran, hingga monitoring tenggat waktu pengumpulan material aset sponsor (logo, video, desain booth).
2. Target Users & Roles
Sistem ini menggunakan struktur otorisasi Role-Based Access Control (RBAC) yang sangat ketat:
•	Tim J4U (Admin/Editor): Memiliki akses Write/Full CRUD ke seluruh sistem. Satu-satunya pihak yang berhak menginput, mengubah, dan menghapus data (Master data, Deal, Pembayaran, Status Material).
•	Dokter (Viewer): Memiliki akses Read-Only. Hanya dapat melihat Dashboard/Final Summary dari sponsor yang mereka inisiasi. Dapat melihat riwayat pembaruan melalui Activity Log, namun tidak dapat mengubah elemen apapun di dalam sistem.
3. Scope of Work (Core Modules)
Module 1: Master Data Management (Katalog Sponsorship)
•	Modul ini digunakan oleh Tim J4U untuk mengatur inventory sponsorship sesuai dengan PDF Prospectus.
•	Master Items: CRUD entitas item tunggal (cth: "Booth 3x3m", "Industry Symposium", "Welcome Reception Naming Rights"). Setiap item memiliki atribut Material Requirement.
•	Master Packages (Tiers): CRUD paket dasar (Diamond, Platinum, White Gold, dll) beserta harga default. Paket ini merelasikan beberapa Master Items.
•	Add-on Items: CRUD item lepasan yang bisa dibeli terpisah.
Module 2: Deal Management (Pembuatan & Kustomisasi Deal)
•	Modul operasional utama ketika Dokter melaporkan hasil negosiasi kepada Tim J4U.
•	Initiation Form: Tim J4U memasukkan data perusahaan sponsor, PIC sponsor, dan Dokter pengundang (Initiator).
•	Package Selection & Customization: Select Base Tier, lalu Item Modification (menghapus item bawaan paket atau menambahkan Add-on).
•	Custom Pricing: Input harga final secara manual (IDR) karena kesepakatan bisa berupa harga flat atau diskon khusus.
•	Payment Terms Configurator: Form dinamis untuk memecah total harga final menjadi beberapa termin (contoh: Termin 1 DP 50% tanggal X, Termin 2 50% tanggal Y).
Module 3: Tracking & Maintenance
•	Modul untuk memantau berjalannya kesepakatan pasca-deal dibuat.
•	Material Tracker: Sistem otomatis meng-generate daftar checklist material yang wajib dikumpulkan sponsor. Tim J4U dapat mengupdate statusnya dan tanggal terimanya.
•	Payment Tracker: Tombol toggle/update status termin pembayaran dari "Pending" menjadi "Paid".
•	Activity Ledger (Audit Trail): Setiap event manipulasi data pada Deal wajib di-log ke dalam database secara otomatis beserta timestamp dan nama user (J4U).
Module 4: Dashboard & Final Summary
•	UI Dashboard ringkas yang menampilkan ringkasan kesepakatan akhir (Final Summary).
•	Bagi Dokter: Menampilkan daftar deals mereka, progres pembayaran, status material, dan lini masa Activity Log.
4. System Architecture & Tech Stack Recommendation
Mengingat kompleksitas state management pada antarmuka Deal Customization dan kebutuhan pembaruan UI secara dinamis (tanpa reload), direkomendasikan menggunakan stack berikut:
•	Backend Framework: Laravel 11
•	Frontend/Reactivity: Livewire 3 & Tailwind CSS
•	Database: PostgreSQL atau MySQL
•	Web Server/Infrastructure: Nginx & Docker
5. Database Schema Overview (Entity Relationship)
Panduan relasi tabel untuk developer:
•	users: id, name, role (enum: j4u, doctor), email, password.
•	sponsors: id, company_name, pic_name, pic_contact.
•	items: id, name, type, material_required (boolean/json).
•	packages: id, name, default_price.
•	package_item (Pivot): package_id, item_id.
•	deals: id, deal_number, doctor_id, sponsor_id, package_id, final_price, status (draft, finalized).
•	deal_items (Pivot): deal_id, item_id, is_addon, custom_price (if any).
•	payment_terms: id, deal_id, description (e.g., "Termin 1"), due_date, amount, status (pending, paid).
•	material_deadlines: id, deal_id, item_id, material_name, due_date, status (pending, received), received_at.
•	activity_logs: id, deal_id, user_id, action (string), details (json: old_value, new_value), created_at.
6. Logic & Event Hooks (Development Notes)
1.	Dynamic Material Generation: Saat Deal di-set menjadi finalized, Listen ke event tersebut. Looping seluruh deal_items, cek jika item tersebut membutuhkan material, lalu jalankan proses insert otomatis ke tabel material_deadlines.
2.	Activity Log Observer: Gunakan Laravel Observers pada model Deal, PaymentTerm, dan MaterialDeadline. Pada method updated(), catat field apa yang berubah (getDirty()) dan insert ke tabel activity_logs.
3.	Route Protection: Terapkan Middleware ketat. Route berawalan /deals/{id}/edit, /deals/{id}/payments, dll, hanya boleh diakses oleh auth()->user()->role === 'j4u'. Dokter hanya boleh mengakses route show atau index.
7. Acceptance Criteria (Definisi Selesai)
•	[ ] Tim J4U dapat membuat paket dari gabungan beberapa item.
•	[ ] Tim J4U dapat membuat form Deal baru, mengubah harga default paket, menambah add-on, dan menetapkan termin pembayaran secara bebas.
•	[ ] Setelah Deal tersimpan, sistem secara otomatis merilis checklist material yang harus ditagih ke sponsor.
•	[ ] Dokter berhasil login dan HANYA bisa melihat halaman Summary dari sponsor yang diundangnya (tidak ada tombol edit/delete).
•	[ ] Setiap klik simpan/ubah oleh J4U pada detail Deal tercermin di riwayat Activity Log.
