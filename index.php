<?php

session_start();

// ============================================================
// KNOWLEDGE BASE — CF Pakar per Rule
// ============================================================
$cf_pakar = [
    // Set 2: Kemampuan Dasar
    'R5' => 0.90,
    'R6' => 0.80,
    'R7' => 0.90,

    // Set 3: Kualitas Bacaan
    'R8a' => 0.90,
    'R8b' => 0.70,
    'R9a' => 0.70,
    'R9b' => 0.80,
    'R10' => 0.95,

    // Set 1: Keputusan Akhir
    'R1'  => 0.90,
    'R2'  => 0.85,
    'R3'  => 0.90,
    'R4'  => 0.95,
];

$rekomendasi = [
    'Iqro'           => 'Mulai dari Iqro jilid 1. Fokus pada pengenalan huruf hijaiyah, pelafalan dasar, dan pemahaman harakat secara bertahap bersama guru pembimbing.',
    "Baca Al-Qur'an" => 'Lanjutkan latihan membaca Al-Qur\'an secara rutin. Tingkatkan kelancaran dengan membaca surat-surat pendek setiap hari minimal 10–15 menit.',
    'Tahsin'         => 'Ikuti program tahsin untuk memperbaiki kualitas bacaan. Fokus pada makhraj huruf yang benar dan penerapan hukum tajwid secara konsisten.',
    'Tahfizh'        => "Selamat! Anda siap memulai program tahfizh (menghafal Al-Qur'an). Pertahankan kualitas bacaan dan mulai hafalan dari Juz 'Amma.",
];

// ============================================================
// INFERENCE ENGINE
// ============================================================

function inferSet2(array $facts, array $cf_pakar): array {
    $trace = [];
    $trace[] = "Fakta masuk ke Set 2: mengenal={$facts['mengenal']}, baca={$facts['baca']}, harakat={$facts['harakat']}, khatam={$facts['khatam']}";

    if ($facts['mengenal'] === 'tidak') {
        $trace[] = "R5 COCOK: mengenal=tidak → kemampuan dasar = Tidak Bisa";
        return ['hasil' => 'Tidak Bisa', 'rule' => 'R5', 'cf_pakar' => $cf_pakar['R5'], 'trace' => $trace];
    }

    if ($facts['mengenal'] === 'ya' && $facts['baca'] === 'ya' &&
        $facts['harakat'] === 'ya'  && $facts['khatam'] === 'ya') {
        $trace[] = "R7 COCOK: mengenal+baca+harakat+khatam=ya → kemampuan dasar = Bisa";
        return ['hasil' => 'Bisa', 'rule' => 'R7', 'cf_pakar' => $cf_pakar['R7'], 'trace' => $trace];
    }

    if ($facts['mengenal'] === 'ya' && $facts['baca'] === 'ya') {
        $trace[] = "R6 COCOK: mengenal+baca=ya, harakat/khatam belum lengkap → kemampuan dasar = Pemula";
        return ['hasil' => 'Pemula', 'rule' => 'R6', 'cf_pakar' => $cf_pakar['R6'], 'trace' => $trace];
    }

    $trace[] = "R5 (fallback): mengenal=ya tapi baca=tidak → kemampuan dasar = Tidak Bisa";
    return ['hasil' => 'Tidak Bisa', 'rule' => 'R5', 'cf_pakar' => $cf_pakar['R5'] * 0.8, 'trace' => $trace];
}

function inferSet3(array $facts, array $cf_pakar): array {
    $trace = [];
    $trace[] = "Fakta masuk ke Set 3: lancar={$facts['lancar']}, makhraj={$facts['makhraj']}, tajwid={$facts['tajwid']}, tahsin={$facts['tahsin']}";

    if ($facts['lancar'] === 'ya' && $facts['makhraj'] === 'ya' &&
        in_array($facts['tajwid'], ['menengah', 'lanjut']) && $facts['tahsin'] === 'ya') {
        $trace[] = "R10 COCOK: lancar+makhraj=ya, tajwid≥menengah, tahsin=ya → kualitas = Mahir";
        return ['hasil' => 'Mahir', 'rule' => 'R10', 'cf_pakar' => $cf_pakar['R10'], 'trace' => $trace];
    }

    if ($facts['lancar'] === 'tidak' && $facts['makhraj'] === 'tidak') {
        $trace[] = "R8a COCOK: lancar=tidak, makhraj=tidak → kualitas = Kurang";
        return ['hasil' => 'Kurang', 'rule' => 'R8a', 'cf_pakar' => $cf_pakar['R8a'], 'trace' => $trace];
    }

    if ($facts['lancar'] === 'tidak' && $facts['makhraj'] === 'ya' && $facts['tahsin'] === 'tidak') {
        $trace[] = "R8b COCOK: lancar=tidak, makhraj=ya, tahsin=tidak → kualitas = Kurang";
        return ['hasil' => 'Kurang', 'rule' => 'R8b', 'cf_pakar' => $cf_pakar['R8b'], 'trace' => $trace];
    }

    if ($facts['lancar'] === 'tidak' && $facts['makhraj'] === 'ya' && $facts['tahsin'] === 'ya') {
        $trace[] = "R9a COCOK: lancar=tidak, makhraj=ya, tahsin=ya → kualitas = Baik";
        return ['hasil' => 'Baik', 'rule' => 'R9a', 'cf_pakar' => $cf_pakar['R9a'], 'trace' => $trace];
    }

    if ($facts['lancar'] === 'ya') {
        $trace[] = "R9b COCOK: lancar=ya (kondisi umum) → kualitas = Baik";
        return ['hasil' => 'Baik', 'rule' => 'R9b', 'cf_pakar' => $cf_pakar['R9b'], 'trace' => $trace];
    }

    $trace[] = "R8a (fallback): kondisi tidak terpetakan → kualitas = Kurang";
    return ['hasil' => 'Kurang', 'rule' => 'R8a', 'cf_pakar' => $cf_pakar['R8a'] * 0.6, 'trace' => $trace];
}

function inferSet1(string $kemDasar, string $kualitas, array $cf_pakar): array {
    $trace = [];
    $trace[] = "Fakta masuk ke Set 1: kemampuan_dasar={$kemDasar}, kualitas_bacaan={$kualitas}";

    if ($kemDasar === 'Bisa' && $kualitas === 'Mahir') {
        $trace[] = "R4 COCOK: kemampuan=Bisa, kualitas=Mahir → Tahfizh";
        return ['hasil' => 'Tahfizh', 'rule' => 'R4', 'cf_pakar' => $cf_pakar['R4'], 'trace' => $trace];
    }

    if ($kemDasar === 'Bisa') {
        $trace[] = "R3 COCOK: kemampuan=Bisa, kualitas={$kualitas} → Tahsin";
        return ['hasil' => 'Tahsin', 'rule' => 'R3', 'cf_pakar' => $cf_pakar['R3'], 'trace' => $trace];
    }

    if ($kemDasar === 'Pemula') {
        $trace[] = "R2 COCOK: kemampuan=Pemula → Baca Al-Qur'an";
        return ['hasil' => "Baca Al-Qur'an", 'rule' => 'R2', 'cf_pakar' => $cf_pakar['R2'], 'trace' => $trace];
    }

    $trace[] = "R1 COCOK: kemampuan=Tidak Bisa → Iqro";
    return ['hasil' => 'Iqro', 'rule' => 'R1', 'cf_pakar' => $cf_pakar['R1'], 'trace' => $trace];
}

function hitungCF(float $cfPakar, float $cfUser): float {
    return round($cfPakar * $cfUser, 4);
}

/**
 * Kombinasi dua nilai CF menggunakan rumus:
 * CF_combine = CF1 + CF2 * (1 - CF1)
 */
function kombinasiCF(float $cf1, float $cf2): float {
    $result = $cf1 + $cf2 * (1 - $cf1);
    // Clamp ke rentang [-1, 1]
    return round(max(-1.0, min(1.0, $result)), 4);
}

function interpretasiCF(float $cf): string {
    if ($cf <= -1.0) return 'Definitely Not';
    if ($cf >= -0.99 && $cf <= -0.8) return 'Almost Certainly Not';
    if ($cf > -0.8  && $cf <= -0.6) return 'Probably Not';
    if ($cf > -0.6  && $cf <= -0.4) return 'Maybe Not';
    if ($cf > -0.4  && $cf <=  0.2) return 'Unknown';
    if ($cf >  0.2  && $cf <=  0.4) return 'Maybe';
    if ($cf >  0.4  && $cf <=  0.6) return 'Probably';
    if ($cf >  0.6  && $cf <=  0.8) return 'Almost Certainly';
    if ($cf >  0.8  && $cf <=  1.0) return 'Definitely';
    return 'Tidak Yakin';
}

// ============================================================
// PROSES FORM
// ============================================================
$result = null;
$errors = [];

$allowed_uc     = ['-1.0', '-0.8', '-0.6', '-0.4', '0.0', '0.4', '0.6', '0.8', '1.0'];
$allowed_yn     = ['ya', 'tidak'];
$allowed_tajwid = ['dasar', 'menengah', 'lanjut'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['mengenal','baca','harakat','khatam','lancar','makhraj','tajwid','tahsin','uc_set2','uc_set3'];
    $input  = [];
    foreach ($fields as $f) {
        $input[$f] = trim($_POST[$f] ?? '');
    }

    foreach (['mengenal','baca','harakat','khatam','lancar','makhraj','tahsin'] as $f) {
        if (!in_array($input[$f], $allowed_yn)) {
            $errors[] = "Field '$f' harus 'ya' atau 'tidak'.";
        }
    }
    if (!in_array($input['tajwid'], $allowed_tajwid)) {
        $errors[] = "Level tajwid tidak valid.";
    }
    foreach (['uc_set2','uc_set3'] as $f) {
        if (!in_array($input[$f], $allowed_uc)) {
            $errors[] = "Tingkat keyakinan untuk '$f' tidak valid.";
        }
    }

    if (empty($errors)) {
        $uc2 = (float) $input['uc_set2'];
        $uc3 = (float) $input['uc_set3'];

        $factsSet2 = [
            'mengenal' => $input['mengenal'],
            'baca'     => $input['baca'],
            'harakat'  => $input['harakat'],
            'khatam'   => $input['khatam'],
        ];
        $factsSet3 = [
            'lancar'  => $input['lancar'],
            'makhraj' => $input['makhraj'],
            'tajwid'  => $input['tajwid'],
            'tahsin'  => $input['tahsin'],
        ];

        $resSet2 = inferSet2($factsSet2, $cf_pakar);
        $resSet3 = inferSet3($factsSet3, $cf_pakar);
        $resSet1 = inferSet1($resSet2['hasil'], $resSet3['hasil'], $cf_pakar);

        // Hitung CF masing-masing set
        $cf_set2 = hitungCF($resSet2['cf_pakar'], $uc2);
        $cf_set3 = hitungCF($resSet3['cf_pakar'], $uc3);

        // Kombinasi CF menggunakan rumus: CF_combine = CF1 + CF2*(1 - CF1)
        $cf_combine = kombinasiCF($cf_set2, $cf_set3);

        // CF final: kombinasi dengan CF pakar Set 1
        $cf_final = kombinasiCF($cf_combine, $resSet1['cf_pakar']);

        $result = [
            'input'        => $input,
            'resSet2'      => $resSet2,
            'resSet3'      => $resSet3,
            'resSet1'      => $resSet1,
            'uc2'          => $uc2,
            'uc3'          => $uc3,
            'cf_set2'      => $cf_set2,
            'cf_set3'      => $cf_set3,
            'cf_combine'   => $cf_combine,
            'cf_final'     => $cf_final,
            'diagnosa'     => $resSet1['hasil'],
            'interpretasi' => interpretasiCF($cf_final),
            'rekomendasi'  => $rekomendasi[$resSet1['hasil']] ?? '-',
        ];
    }
}

function badgeClass(string $d): string {
    $map = [
        'Iqro'           => 'badge-iqro',
        "Baca Al-Qur'an" => 'badge-baca',
        'Tahsin'         => 'badge-tahsin',
        'Tahfizh'        => 'badge-tahfizh',
    ];
    return $map[$d] ?? 'badge-iqro';
}

function pct(float $cf): string {
    return round($cf * 100) . '%';
}

function isChecked(string $field, string $value, array $post, string $default = ''): string {
    if (isset($post[$field])) {
        return $post[$field] === $value ? 'checked' : '';
    }
    return $default === $value ? 'checked' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistem Pakar — Evaluasi Membaca Al-Qur'an</title>
<style>
:root {
    --bg:       #f7f6f2;
    --surface:  #ffffff;
    --border:   #e2e0d8;
    --text:     #1a1a18;
    --muted:    #6b6a65;
    --hint:     #9e9c96;
    --green:    #1D9E75;
    --green-bg: #E1F5EE;
    --blue:     #185FA5;
    --blue-bg:  #E6F1FB;
    --amber:    #854F0B;
    --amber-bg: #FAEEDA;
    --teal:     #3B6D11;
    --teal-bg:  #EAF3DE;
    --red:      #A32D2D;
    --red-bg:   #FCEBEB;
    --radius:   10px;
    --radius-sm:6px;
    --shadow:   0 1px 3px rgba(0,0,0,.07), 0 4px 12px rgba(0,0,0,.05);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
    font-size: 15px;
    line-height: 1.6;
    min-height: 100vh;
    padding: 2rem 1rem 4rem;
}
.container { max-width: 720px; margin: 0 auto; }

/* Header */
.header { margin-bottom: 2rem; }
.header h1 { font-size: 22px; font-weight: 600; letter-spacing: -.3px; margin-bottom: 4px; }
.header p  { font-size: 13px; color: var(--muted); }
.header .tag {
    display: inline-block;
    font-size: 11px; font-weight: 500;
    background: var(--green-bg); color: var(--green);
    padding: 3px 10px; border-radius: 99px; margin-bottom: 8px;
}

/* Card */
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.5rem;
    margin-bottom: 1rem;
    box-shadow: var(--shadow);
}
.card-title {
    font-size: 11px; font-weight: 600; color: var(--muted);
    text-transform: uppercase; letter-spacing: .07em;
    margin-bottom: 1rem; padding-bottom: .5rem;
    border-bottom: 1px solid var(--border);
}

/* Form */
.q-row { margin-bottom: 1rem; }
.q-row:last-child { margin-bottom: 0; }
.q-label { font-size: 14px; font-weight: 500; margin-bottom: 3px; }
.q-hint  { font-size: 12px; color: var(--hint); margin-bottom: 8px; }

/* Radio pill group */
.radio-group { display: flex; gap: 8px; flex-wrap: wrap; }
.radio-opt { position: relative; }
.radio-opt input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0; height: 0;
    pointer-events: none;
}
.radio-opt label {
    display: block;
    padding: 5px 16px;
    border: 1px solid var(--border);
    border-radius: 99px;
    font-size: 13px;
    color: var(--muted);
    cursor: pointer;
    transition: all .15s;
    user-select: none;
    background: var(--bg);
}
.radio-opt label:hover {
    border-color: var(--green);
    color: var(--green);
}
.radio-opt input[type="radio"]:checked + label {
    border-color: var(--green);
    background: var(--green-bg);
    color: var(--green);
    font-weight: 500;
}
.radio-opt input[type="radio"][value="tidak"]:checked + label {
    border-color: var(--red);
    background: var(--red-bg);
    color: var(--red);
}
.radio-opt input[type="radio"][value="-1.0"]:checked + label,
.radio-opt input[type="radio"][value="-0.8"]:checked + label,
.radio-opt input[type="radio"][value="-0.6"]:checked + label,
.radio-opt input[type="radio"][value="-0.4"]:checked + label {
    border-color: var(--red);
    background: var(--red-bg);
    color: var(--red);
}
.radio-opt input[type="radio"][value="0.0"]:checked + label {
    border-color: var(--muted);
    background: var(--bg);
    color: var(--muted);
}

select {
    width: 100%;
    padding: 7px 10px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 14px;
    color: var(--text);
    background: var(--surface);
    cursor: pointer;
}
select:focus { outline: 2px solid var(--green); outline-offset: 1px; }

/* Submit */
.btn-submit {
    width: 100%; padding: 12px;
    background: var(--text); color: #fff;
    border: none; border-radius: var(--radius);
    font-size: 15px; font-weight: 500;
    cursor: pointer;
    transition: opacity .15s, transform .1s;
    margin-top: .5rem;
}
.btn-submit:hover  { opacity: .88; }
.btn-submit:active { transform: scale(.99); }

/* Result */
.result-section { margin-top: 1.5rem; }
.diagnosa-header { display: flex; align-items: center; gap: 12px; margin-bottom: 1.25rem; }
.diagnosa-header h2 { font-size: 16px; font-weight: 500; color: var(--muted); }

/* Badges */
.badge { display: inline-block; padding: 4px 16px; border-radius: 99px; font-size: 13px; font-weight: 600; }
.badge-iqro    { background: var(--red-bg);   color: var(--red); }
.badge-baca    { background: var(--blue-bg);  color: var(--blue); }
.badge-tahsin  { background: var(--amber-bg); color: var(--amber); }
.badge-tahfizh { background: var(--teal-bg);  color: var(--teal); }

/* CF bars */
.cf-group { margin-bottom: .9rem; }
.cf-meta { display: flex; justify-content: space-between; font-size: 12px; color: var(--muted); margin-bottom: 4px; }
.cf-val  { font-weight: 600; color: var(--text); }
.cf-bar-bg { height: 8px; background: var(--bg); border-radius: 99px; overflow: hidden; border: 1px solid var(--border); }
.cf-bar-fill { height: 100%; border-radius: 99px; transition: width .4s ease; }
.cf-int { font-size: 11px; color: var(--hint); margin-top: 3px; }

/* Step trace */
.step-list { list-style: none; }
.step-list li { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 8px; font-size: 13px; line-height: 1.5; }
.step-num {
    min-width: 22px; height: 22px; border-radius: 50%;
    background: var(--bg); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 600; color: var(--muted);
    flex-shrink: 0; margin-top: 1px;
}
code {
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 12px; background: var(--bg);
    padding: 1px 6px; border-radius: 4px;
    border: 1px solid var(--border); color: var(--muted);
}

/* Formula block */
.formula {
    background: var(--bg); border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 10px 14px;
    font-family: 'Consolas', monospace;
    font-size: 12px; color: var(--muted);
    line-height: 1.8; margin: 8px 0;
    white-space: pre-wrap;
}

/* Rumus highlight */
.formula-title {
    font-size: 11px; font-weight: 600; color: var(--muted);
    text-transform: uppercase; letter-spacing: .06em;
    margin-bottom: 4px;
}
.formula-box {
    background: var(--blue-bg);
    border: 1px solid #AECEF0;
    border-radius: var(--radius-sm);
    padding: 8px 14px;
    font-family: 'Consolas', monospace;
    font-size: 13px; color: var(--blue);
    font-weight: 600;
    margin: 6px 0 10px;
    letter-spacing: .01em;
}

/* Rekomendasi */
.rekom-box {
    background: var(--green-bg); border: 1px solid #9FE1CB;
    border-radius: var(--radius);
    padding: 1rem 1.25rem;
    font-size: 14px; color: #085041; line-height: 1.6;
}
.rekom-box strong { display: block; margin-bottom: 4px; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }

/* Error */
.error-box {
    background: var(--red-bg); border: 1px solid #F7C1C1;
    border-radius: var(--radius);
    padding: .75rem 1rem; color: var(--red);
    font-size: 13px; margin-bottom: 1rem;
}
.error-box ul { padding-left: 1.2rem; }

@media (max-width: 480px) {
    .radio-group { gap: 6px; }
    .radio-opt label { padding: 5px 12px; }
}
</style>
</head>
<body>
<div class="container">

    <!-- Header -->
    <div class="header">
        <span class="tag">Sistem Pakar</span>
        <h1>Evaluasi Kemampuan Membaca Al-Qur'an</h1>
        <p>Kelompok 4</p>
    </div>

    <!-- Error -->
    <?php if (!empty($errors)): ?>
    <div class="error-box">
        <strong>Terdapat kesalahan input:</strong>
        <ul>
            <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" action="">

        <!-- SET 2 -->
        <div class="card">
            <div class="card-title">Set 2 &mdash; Kemampuan Dasar (Rule 5–7)</div>

            <div class="q-row">
                <div class="q-label">1. Mengenal huruf hijaiyah?</div>
                <div class="q-hint">Bisa membedakan dan menyebut nama huruf ا ب ت ث ...</div>
                <div class="radio-group">
                    <div class="radio-opt">
                        <input type="radio" name="mengenal" id="s2_mengenal_ya" value="ya"
                            <?= isChecked('mengenal','ya',$_POST,'ya') ?>>
                        <label for="s2_mengenal_ya">Ya</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="mengenal" id="s2_mengenal_tidak" value="tidak"
                            <?= isChecked('mengenal','tidak',$_POST,'ya') ?>>
                        <label for="s2_mengenal_tidak">Tidak</label>
                    </div>
                </div>
            </div>

            <div class="q-row">
                <div class="q-label">2. Bisa membaca huruf hijaiyah?</div>
                <div class="q-hint">Bisa melafalkan huruf saat melihatnya dalam teks</div>
                <div class="radio-group">
                    <div class="radio-opt">
                        <input type="radio" name="baca" id="s2_baca_ya" value="ya"
                            <?= isChecked('baca','ya',$_POST,'ya') ?>>
                        <label for="s2_baca_ya">Ya</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="baca" id="s2_baca_tidak" value="tidak"
                            <?= isChecked('baca','tidak',$_POST,'ya') ?>>
                        <label for="s2_baca_tidak">Tidak</label>
                    </div>
                </div>
            </div>

            <div class="q-row">
                <div class="q-label">3. Paham harakat (tanda baca)?</div>
                <div class="q-hint">Fathah (a), kasrah (i), dhammah (u), tanwin, sukun, tasydid</div>
                <div class="radio-group">
                    <div class="radio-opt">
                        <input type="radio" name="harakat" id="s2_harakat_ya" value="ya"
                            <?= isChecked('harakat','ya',$_POST,'ya') ?>>
                        <label for="s2_harakat_ya">Ya</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="harakat" id="s2_harakat_tidak" value="tidak"
                            <?= isChecked('harakat','tidak',$_POST,'ya') ?>>
                        <label for="s2_harakat_tidak">Tidak</label>
                    </div>
                </div>
            </div>

            <div class="q-row">
                <div class="q-label">4. Sudah khatam Iqro?</div>
                <div class="radio-group">
                    <div class="radio-opt">
                        <input type="radio" name="khatam" id="s2_khatam_ya" value="ya"
                            <?= isChecked('khatam','ya',$_POST,'ya') ?>>
                        <label for="s2_khatam_ya">Ya</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="khatam" id="s2_khatam_tidak" value="tidak"
                            <?= isChecked('khatam','tidak',$_POST,'ya') ?>>
                        <label for="s2_khatam_tidak">Tidak</label>
                    </div>
                </div>
            </div>

            <div class="q-row">
                <div class="q-label">Tingkat keyakinan jawaban kemampuan dasar (CF User)</div>
                <div class="radio-group">
                    <div class="radio-opt">
                        <input type="radio" name="uc_set2" id="s2_uc_n10" value="-1.0"
                            <?= isChecked('uc_set2','-1.0',$_POST,'1.0') ?>>
                        <label for="s2_uc_n10">Definitely Not (−1.0)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set2" id="s2_uc_n08" value="-0.8"
                            <?= isChecked('uc_set2','-0.8',$_POST,'1.0') ?>>
                        <label for="s2_uc_n08">Almost Certainly Not (−0.8)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set2" id="s2_uc_n06" value="-0.6"
                            <?= isChecked('uc_set2','-0.6',$_POST,'1.0') ?>>
                        <label for="s2_uc_n06">Probably Not (−0.6)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set2" id="s2_uc_n04" value="-0.4"
                            <?= isChecked('uc_set2','-0.4',$_POST,'1.0') ?>>
                        <label for="s2_uc_n04">Maybe Not (−0.4)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set2" id="s2_uc_00" value="0.0"
                            <?= isChecked('uc_set2','0.0',$_POST,'1.0') ?>>
                        <label for="s2_uc_00">Unknown (0.0)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set2" id="s2_uc_04" value="0.4"
                            <?= isChecked('uc_set2','0.4',$_POST,'1.0') ?>>
                        <label for="s2_uc_04">Maybe (0.4)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set2" id="s2_uc_06" value="0.6"
                            <?= isChecked('uc_set2','0.6',$_POST,'1.0') ?>>
                        <label for="s2_uc_06">Probably (0.6)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set2" id="s2_uc_08" value="0.8"
                            <?= isChecked('uc_set2','0.8',$_POST,'1.0') ?>>
                        <label for="s2_uc_08">Almost Certainly (0.8)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set2" id="s2_uc_10" value="1.0"
                            <?= isChecked('uc_set2','1.0',$_POST,'1.0') ?>>
                        <label for="s2_uc_10">Definitely (1.0)</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- SET 3 -->
        <div class="card">
            <div class="card-title">Set 3 &mdash; Kualitas Bacaan (Rule 8–10)</div>

            <div class="q-row">
                <div class="q-label">5. Lancar membaca Al-Qur'an?</div>
                <div class="q-hint">Tidak terbata-bata, ritme bacaan mengalir</div>
                <div class="radio-group">
                    <div class="radio-opt">
                        <input type="radio" name="lancar" id="s3_lancar_ya" value="ya"
                            <?= isChecked('lancar','ya',$_POST,'ya') ?>>
                        <label for="s3_lancar_ya">Ya</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="lancar" id="s3_lancar_tidak" value="tidak"
                            <?= isChecked('lancar','tidak',$_POST,'ya') ?>>
                        <label for="s3_lancar_tidak">Tidak</label>
                    </div>
                </div>
            </div>

            <div class="q-row">
                <div class="q-label">6. Makhraj huruf tepat?</div>
                <div class="q-hint">Tempat keluarnya huruf dari organ fonetik sudah benar</div>
                <div class="radio-group">
                    <div class="radio-opt">
                        <input type="radio" name="makhraj" id="s3_makhraj_ya" value="ya"
                            <?= isChecked('makhraj','ya',$_POST,'ya') ?>>
                        <label for="s3_makhraj_ya">Ya</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="makhraj" id="s3_makhraj_tidak" value="tidak"
                            <?= isChecked('makhraj','tidak',$_POST,'ya') ?>>
                        <label for="s3_makhraj_tidak">Tidak</label>
                    </div>
                </div>
            </div>

            <div class="q-row">
                <div class="q-label">7. Level penguasaan tajwid</div>
                <div class="q-hint">Hukum nun sukun &amp; tanwin, hukum mad, mim sukun, waqaf, ibtida</div>
                <select name="tajwid">
                    <option value="dasar"    <?= (isset($_POST['tajwid']) && $_POST['tajwid']==='dasar')    ? 'selected' : '' ?>>Dasar</option>
                    <option value="menengah" <?= (!isset($_POST['tajwid']) || $_POST['tajwid']==='menengah') ? 'selected' : '' ?>>Menengah</option>
                    <option value="lanjut"   <?= (isset($_POST['tajwid']) && $_POST['tajwid']==='lanjut')   ? 'selected' : '' ?>>Lanjut</option>
                </select>
            </div>

            <div class="q-row">
                <div class="q-label">8. Pernah mengikuti program tahsin?</div>
                <div class="radio-group">
                    <div class="radio-opt">
                        <input type="radio" name="tahsin" id="s3_tahsin_ya" value="ya"
                            <?= isChecked('tahsin','ya',$_POST,'ya') ?>>
                        <label for="s3_tahsin_ya">Ya</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="tahsin" id="s3_tahsin_tidak" value="tidak"
                            <?= isChecked('tahsin','tidak',$_POST,'ya') ?>>
                        <label for="s3_tahsin_tidak">Tidak</label>
                    </div>
                </div>
            </div>

            <div class="q-row">
                <div class="q-label">Tingkat keyakinan jawaban kualitas bacaan (CF User)</div>
                <div class="radio-group">
                    <div class="radio-opt">
                        <input type="radio" name="uc_set3" id="s3_uc_n10" value="-1.0"
                            <?= isChecked('uc_set3','-1.0',$_POST,'1.0') ?>>
                        <label for="s3_uc_n10">Definitely Not (−1.0)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set3" id="s3_uc_n08" value="-0.8"
                            <?= isChecked('uc_set3','-0.8',$_POST,'1.0') ?>>
                        <label for="s3_uc_n08">Almost Certainly Not (−0.8)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set3" id="s3_uc_n06" value="-0.6"
                            <?= isChecked('uc_set3','-0.6',$_POST,'1.0') ?>>
                        <label for="s3_uc_n06">Probably Not (−0.6)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set3" id="s3_uc_n04" value="-0.4"
                            <?= isChecked('uc_set3','-0.4',$_POST,'1.0') ?>>
                        <label for="s3_uc_n04">Maybe Not (−0.4)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set3" id="s3_uc_00" value="0.0"
                            <?= isChecked('uc_set3','0.0',$_POST,'1.0') ?>>
                        <label for="s3_uc_00">Unknown (0.0)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set3" id="s3_uc_04" value="0.4"
                            <?= isChecked('uc_set3','0.4',$_POST,'1.0') ?>>
                        <label for="s3_uc_04">Maybe (0.4)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set3" id="s3_uc_06" value="0.6"
                            <?= isChecked('uc_set3','0.6',$_POST,'1.0') ?>>
                        <label for="s3_uc_06">Probably (0.6)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set3" id="s3_uc_08" value="0.8"
                            <?= isChecked('uc_set3','0.8',$_POST,'1.0') ?>>
                        <label for="s3_uc_08">Almost Certainly (0.8)</label>
                    </div>
                    <div class="radio-opt">
                        <input type="radio" name="uc_set3" id="s3_uc_10" value="1.0"
                            <?= isChecked('uc_set3','1.0',$_POST,'1.0') ?>>
                        <label for="s3_uc_10">Definitely (1.0)</label>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-submit">Jalankan Inferensi Forward Chaining &rarr;</button>
    </form>

    <!-- HASIL -->
    <?php if ($result): ?>
    <div class="result-section">

        <!-- Diagnosa badge + CF bars -->
        <div class="card">
            <div class="diagnosa-header">
                <h2>Hasil diagnosa:</h2>
                <span class="badge <?= badgeClass($result['diagnosa']) ?>">
                    <?= htmlspecialchars($result['diagnosa']) ?>
                </span>
            </div>

            <?php
            $bars = [
                [
                    'label' => 'CF kemampuan dasar (Set 2)',
                    'sub'   => "Rule {$result['resSet2']['rule']} — {$result['resSet2']['hasil']}",
                    'cf'    => $result['cf_set2'],
                    'color' => '#1D9E75',
                ],
                [
                    'label' => 'CF kualitas bacaan (Set 3)',
                    'sub'   => "Rule {$result['resSet3']['rule']} — {$result['resSet3']['hasil']}",
                    'cf'    => $result['cf_set3'],
                    'color' => '#185FA5',
                ],
                [
                    'label' => 'CF gabungan (Set 2 ⊕ Set 3)',
                    'sub'   => "CF_combine = CF1 + CF2×(1−CF1)",
                    'cf'    => $result['cf_combine'],
                    'color' => '#854F0B',
                ],
                [
                    'label' => 'CF diagnosa akhir (Set 1)',
                    'sub'   => "Rule {$result['resSet1']['rule']} — {$result['diagnosa']} ({$result['interpretasi']})",
                    'cf'    => $result['cf_final'],
                    'color' => '#3B6D11',
                ],
            ];
            foreach ($bars as $bar):
                $barPct = pct(abs($bar['cf']));
            ?>
            <div class="cf-group">
                <div class="cf-meta">
                    <span><?= $bar['label'] ?></span>
                    <span class="cf-val"><?= $bar['cf'] ?></span>
                </div>
                <div class="cf-bar-bg">
                    <div class="cf-bar-fill" style="width:<?= $barPct ?>;background:<?= $bar['color'] ?>"></div>
                </div>
                <div class="cf-int"><?= htmlspecialchars($bar['sub']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Langkah Perhitungan Manual -->
        <div class="card">
            <div class="card-title">Langkah Perhitungan Manual (Forward Chaining + CF Combine)</div>

            <div class="formula-title">Rumus Kombinasi CF yang Digunakan</div>
            <div class="formula-box">CF_combine = CF1 + CF2 &times; (1 &minus; CF1)</div>

            <ul class="step-list">
                <li>
                    <div class="step-num">1</div>
                    <div>
                        <strong>Fakta awal dimasukkan ke Working Memory:</strong><br>
                        mengenal=<code><?= $result['input']['mengenal'] ?></code>,
                        baca=<code><?= $result['input']['baca'] ?></code>,
                        harakat=<code><?= $result['input']['harakat'] ?></code>,
                        khatam=<code><?= $result['input']['khatam'] ?></code>,
                        lancar=<code><?= $result['input']['lancar'] ?></code>,
                        makhraj=<code><?= $result['input']['makhraj'] ?></code>,
                        tajwid=<code><?= $result['input']['tajwid'] ?></code>,
                        tahsin=<code><?= $result['input']['tahsin'] ?></code>
                    </div>
                </li>
                <li>
                    <div class="step-num">2</div>
                    <div>
                        <strong>Mesin menelusuri Set 2 (rule matching dari atas ke bawah):</strong><br>
                        <?php foreach ($result['resSet2']['trace'] as $t): ?>
                            &rarr; <?= htmlspecialchars($t) ?><br>
                        <?php endforeach; ?>
                        <div class="formula">CF(Set2) = CF_pakar(<?= $result['resSet2']['rule'] ?>) × CF_user
         = <?= $result['resSet2']['cf_pakar'] ?> × <?= $result['uc2'] ?>

         = <?= $result['cf_set2'] ?></div>
                    </div>
                </li>
                <li>
                    <div class="step-num">3</div>
                    <div>
                        <strong>Fakta baru ditambahkan ke Working Memory, mesin lanjut ke Set 3:</strong><br>
                        <?php foreach ($result['resSet3']['trace'] as $t): ?>
                            &rarr; <?= htmlspecialchars($t) ?><br>
                        <?php endforeach; ?>
                        <div class="formula">CF(Set3) = CF_pakar(<?= $result['resSet3']['rule'] ?>) × CF_user
         = <?= $result['resSet3']['cf_pakar'] ?> × <?= $result['uc3'] ?>

         = <?= $result['cf_set3'] ?></div>
                    </div>
                </li>
                <li>
                    <div class="step-num">4</div>
                    <div>
                        <strong>Kombinasi CF dua premis menggunakan rumus CF_combine:</strong>
                        <div class="formula">CF1 (Set 2) = <?= $result['cf_set2'] ?>
CF2 (Set 3) = <?= $result['cf_set3'] ?>

CF_combine = CF1 + CF2 × (1 − CF1)
           = <?= $result['cf_set2'] ?> + <?= $result['cf_set3'] ?> × (1 − <?= $result['cf_set2'] ?>)
           = <?= $result['cf_set2'] ?> + <?= $result['cf_set3'] ?> × <?= round(1 - $result['cf_set2'], 4) ?>

           = <?= $result['cf_combine'] ?></div>
                    </div>
                </li>
                <li>
                    <div class="step-num">5</div>
                    <div>
                        <strong>Mesin lanjut ke Set 1 untuk keputusan akhir:</strong><br>
                        <?php foreach ($result['resSet1']['trace'] as $t): ?>
                            &rarr; <?= htmlspecialchars($t) ?><br>
                        <?php endforeach; ?>
                        <div class="formula">CF_combine (gabungan)  = <?= $result['cf_combine'] ?>
CF_pakar (<?= $result['resSet1']['rule'] ?>)         = <?= $result['resSet1']['cf_pakar'] ?>

CF_final = CF_combine + CF_pakar × (1 − CF_combine)
         = <?= $result['cf_combine'] ?> + <?= $result['resSet1']['cf_pakar'] ?> × (1 − <?= $result['cf_combine'] ?>)
         = <?= $result['cf_combine'] ?> + <?= $result['resSet1']['cf_pakar'] ?> × <?= round(1 - $result['cf_combine'], 4) ?>

         = <?= $result['cf_final'] ?></div>
                    </div>
                </li>
                <li>
                    <div class="step-num">6</div>
                    <div>
                        <strong>Interpretasi nilai CF:</strong>
                        <div class="formula">CF = -1.0            => Definitely Not
CF = -0.8            => Almost Certainly Not
CF = -0.6            => Probably Not
CF = -0.4            => Maybe Not
CF = -0.2 s.d. 0.2  => Unknown
CF = 0.4             => Maybe
CF = 0.6             => Probably
CF = 0.8             => Almost Certainly
CF = 1.0             => Definitely

CF akhir = <?= $result['cf_final'] ?> (<?= pct($result['cf_final']) ?>)

→ Sistem <?= $result['interpretasi'] ?>

  dengan diagnosa <?= htmlspecialchars($result['diagnosa']) ?></div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Rekomendasi -->
        <div class="rekom-box">
            <strong>Rekomendasi Pembelajaran</strong>
            <?= htmlspecialchars($result['rekomendasi']) ?>
        </div>

    </div>
    <?php endif; ?>

</div>
</body>
</html>