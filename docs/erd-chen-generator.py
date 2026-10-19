"""Chen ERD (black background, white shapes) for SIKAD-BUMDESMA.

Entities/attributes/relationships mirror the real Indonesian schema.
Usage: python gen_erd.py  (writes docs/erd-chen.png)
"""
from PIL import Image, ImageDraw, ImageFont

W, H = 2500, 1800
FD = r'C:\Windows\Fonts\\'
AR = ImageFont.truetype(FD + 'tahoma.ttf', 24)
AR20 = ImageFont.truetype(FD + 'tahoma.ttf', 20)
ARB = ImageFont.truetype(FD + 'tahomabd.ttf', 26)
ARC = ImageFont.truetype(FD + 'tahomabd.ttf', 28)
ART = ImageFont.truetype(FD + 'tahomabd.ttf', 32)

img = Image.new('RGB', (W, H), 'black')
d = ImageDraw.Draw(img)
LW = 3


def text(c, cx, cy, s, font=AR, fill='black'):
    d.text((cx, cy), s, font=font, fill=fill, anchor='mm')


def ellipse(cx, cy, w, h, label, key=False):
    d.ellipse([cx - w / 2, cy - h / 2, cx + w / 2, cy + h / 2],
              fill='white', outline='white', width=LW)
    text(d, cx, cy - (2 if key else 0), label, AR20)
    if key:
        b = d.textbbox((0, 0), label, font=AR20)
        wt = b[2] - b[0]
        d.line([(cx - wt / 2, cy + 13), (cx + wt / 2, cy + 13)],
               fill='white', width=2)


def box(cx, cy, label):
    b = d.textbbox((0, 0), label, font=ARB)
    w = max(200, b[2] - b[0] + 50)
    d.rectangle([cx - w / 2, cy - 36, cx + w / 2, cy + 36],
                fill='white', outline='white', width=LW)
    text(d, cx, cy, label, ARB)
    return w


def diamond(cx, cy, label):
    w, h = 150, 80
    d.polygon([(cx, cy - h / 2), (cx + w / 2, cy),
               (cx, cy + h / 2), (cx - w / 2, cy)],
              fill='white', outline='white')
    text(d, cx, cy, label, AR)


def seg(pts):
    d.line(pts, fill='white', width=LW, joint='curve')


def card(cx, cy, s):
    text(d, cx, cy, s, ARC, fill='white')


def eatt(cx, cy, label, key=False):
    b = d.textbbox((0, 0), label, font=AR20)
    ellipse(cx, cy, max(150, b[2] - b[0] + 56), 52, label, key)


d.text((W - 40, 34), 'ERD Sistem Informasi Absensi (Chen)',
       font=ART, fill='white', anchor='rm')

# ---------------- entities ----------------
ENTS = ['jabatan', 'jadwal_kerja', 'hari_libur', 'users', 'karyawan',
        'kehadiran', 'lokasi_kehadiran', 'penggajian', 'kuota_cuti',
        'pengajuan_cuti', 'komponen_gaji', 'penghargaan', 'hukuman',
        'kunjungan']
POS = {
    'jabatan': (620, 200), 'jadwal_kerja': (1300, 200),
    'hari_libur': (1980, 200),
    'users': (380, 650), 'karyawan': (950, 650),
    'kehadiran': (1600, 650), 'lokasi_kehadiran': (2050, 650),
    'penggajian': (250, 1100), 'kuota_cuti': (950, 1100),
    'pengajuan_cuti': (1500, 1100), 'komponen_gaji': (2050, 1100),
    'penghargaan': (550, 1500), 'hukuman': (1250, 1500),
    'kunjungan': (1950, 1500),
}
BW = {name: box(cx, cy, name) for name, (cx, cy) in POS.items()}


def top(name):
    cx, cy = POS[name]
    return (cx, cy - 36)


def bottom(name):
    cx, cy = POS[name]
    return (cx, cy + 36)


def left(name):
    cx, cy = POS[name]
    return (cx - BW[name] / 2, cy)


def right(name):
    cx, cy = POS[name]
    return (cx + BW[name] / 2, cy)


# ---------------- attributes ----------------
# (entity, label, key, cx, cy, attach): attach T(op)/B(ottom)/R(ight)
ATTRS = [
    ('jabatan', 'id', True, 545, 60, 'T'),
    ('jabatan', 'nama', False, 695, 60, 'T'),
    ('jadwal_kerja', 'id', True, 1010, 60, 'T'),
    ('jadwal_kerja', 'nama', False, 1200, 60, 'T'),
    ('jadwal_kerja', 'jam_masuk', False, 1390, 60, 'T'),
    ('jadwal_kerja', 'jam_pulang', False, 1580, 60, 'T'),
    ('hari_libur', 'id', True, 1830, 340, 'B'),
    ('hari_libur', 'tanggal', False, 1980, 340, 'B'),
    ('hari_libur', 'nama', False, 2130, 340, 'B'),
    ('users', 'id', True, 155, 834, 'B'),
    ('users', 'name', False, 305, 834, 'B'),
    ('users', 'email', False, 455, 834, 'B'),
    ('users', 'role', False, 605, 834, 'B'),
    ('karyawan', 'id', True, 610, 486, 'T'),
    ('karyawan', 'kode_karyawan', False, 840, 486, 'T'),
    ('karyawan', 'nama', False, 1070, 486, 'T'),
    ('karyawan', 'telepon', False, 1300, 486, 'T'),
    ('kehadiran', 'id', True, 1190, 830, 'B'),
    ('kehadiran', 'tanggal', False, 1400, 830, 'B'),
    ('kehadiran', 'waktu_masuk', False, 1610, 830, 'B'),
    ('kehadiran', 'waktu_pulang', False, 1820, 830, 'B'),
    ('kehadiran', 'status', False, 2030, 830, 'B'),
    ('lokasi_kehadiran', 'id', True, 1880, 490, 'T'),
    ('lokasi_kehadiran', 'nama', False, 2080, 490, 'T'),
    ('lokasi_kehadiran', 'radius_meter', False, 2280, 490, 'T'),
    ('penggajian', 'id', True, 640, 1020, 'R'),
    ('penggajian', 'periode_mulai', False, 640, 1120, 'R'),
    ('penggajian', 'gaji_bersih', False, 640, 1220, 'R'),
    ('penggajian', 'status', False, 640, 1320, 'R'),
    ('kuota_cuti', 'id', True, 770, 1260, 'B'),
    ('kuota_cuti', 'tahun', False, 950, 1260, 'B'),
    ('kuota_cuti', 'jatah_hari', False, 1130, 1260, 'B'),
    ('pengajuan_cuti', 'id', True, 1290, 1260, 'B'),
    ('pengajuan_cuti', 'jenis', False, 1500, 1260, 'B'),
    ('pengajuan_cuti', 'tanggal_mulai', False, 1710, 1260, 'B'),
    ('pengajuan_cuti', 'status', False, 1920, 1260, 'B'),
    ('komponen_gaji', 'id', True, 2290, 950, 'R'),
    ('komponen_gaji', 'nama', False, 2290, 1050, 'R'),
    ('komponen_gaji', 'jenis', False, 2290, 1150, 'R'),
    ('komponen_gaji', 'nominal', False, 2290, 1250, 'R'),
    ('penghargaan', 'id', True, 380, 1660, 'B'),
    ('penghargaan', 'jenis', False, 550, 1660, 'B'),
    ('penghargaan', 'nominal', False, 720, 1660, 'B'),
    ('hukuman', 'id', True, 1080, 1660, 'B'),
    ('hukuman', 'jenis', False, 1250, 1660, 'B'),
    ('hukuman', 'poin', False, 1420, 1660, 'B'),
    ('kunjungan', 'id', True, 1780, 1660, 'B'),
    ('kunjungan', 'judul', False, 1950, 1660, 'B'),
    ('kunjungan', 'tanggal', False, 2120, 1660, 'B'),
]
for ent, label, key, cx, cy, att in ATTRS:
    eatt(cx, cy, label, key)
    b = d.textbbox((0, 0), label, font=AR20)
    halfw = max(150, b[2] - b[0] + 56) / 2
    ex, ey = POS[ent]
    if att == 'T':
        seg([top(ent), (cx, cy + 26)])
    elif att == 'B':
        seg([bottom(ent), (cx, cy - 26)])
    else:
        seg([right(ent), (cx - halfw, cy)])

# ---------------- relationships ----------------
# (label, diamond cx, cy, path1 points, card1, path2 points, card2)
RELS = [
    ('memiliki', 665, 650, [(480, 650)], '1', [(850, 650)], '1'),
    ('menjabat', 400, 486, [(620, 235)], '1', [(850, 650)], 'M'),
    ('mengikuti', 1500, 486, [(1300, 235)], '1', [(1050, 650)], 'M'),
    ('mencatat', 1275, 650, [(1050, 650)], '1', [(1500, 650)], 'M'),
    ('dicek_di', 1825, 650, [(1700, 650)], 'M', [(1890, 650)], '1'),
    ('memiliki', 950, 880, [(950, 685)], '1', [(950, 1065)], 'M'),
    ('mengajukan', 1100, 950, [(1000, 685)], '1', [(1400, 1065)], 'M'),
    ('mengulas', 850, 1000, [(550, 686)], '1', [(1400, 1080)], 'M'),
    ('menerima', 660, 940, [(850, 650)], '1', [(300, 1065)], 'M'),
    ('memiliki', 1650, 940, [(1030, 685)], '1', [(1915, 1100)], 'M'),
    ('meraih', 400, 1180, [(850, 700), (550, 750)], '1',
     [(550, 1465)], 'M'),
    ('menerima', 1310, 1100, [(1000, 685)], '1', [(1250, 1465)], 'M'),
    ('melakukan', 1700, 1150, [(1050, 700)], '1', [(1950, 1465)], 'M'),
]
for label, cx, cy, p1, c1, p2, c2 in RELS:
    diamond(cx, cy, label)
    seg(p1 + [(cx, cy)])
    seg([(cx, cy)] + p2)
    card((cx + p1[-1][0]) / 2, (cy + p1[-1][1]) / 2 - 18, c1)
    card((cx + p2[0][0]) / 2, (cy + p2[0][1]) / 2 - 18, c2)

img.save(r'D:\KULIAH\MAGANG\sistem_informasi_absensi\docs\erd-chen.png')
print('erd-chen.png tersimpan, ukuran:', img.size)
