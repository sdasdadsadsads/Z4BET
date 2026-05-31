<?php
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    $raw = file_get_contents('php://input');
    $input = json_decode($raw ?: '{}', true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'msg' => 'Invalid JSON body'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $token = trim($input['apiKey'] ?? '');
    if ($token === '') {
        http_response_code(400);
        echo json_encode(['code' => 400, 'msg' => 'กรุณาใส่ Suno API Key'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $api = $_GET['api'];
    $baseUrl = 'https://api.sunoapi.org';
    $method = 'GET';
    $path = '';
    $payload = null;

    if ($api === 'generate-music') {
        $method = 'POST';
        $path = '/api/v1/generate';
        $payload = $input['payload'] ?? [];
    } elseif ($api === 'generate-lyrics') {
        $method = 'POST';
        $path = '/api/v1/lyrics';
        $payload = $input['payload'] ?? [];
    } elseif ($api === 'music-status') {
        $taskId = urlencode(trim($input['taskId'] ?? ''));
        $path = '/api/v1/generate/record-info?taskId=' . $taskId;
    } elseif ($api === 'lyrics-status') {
        $taskId = urlencode(trim($input['taskId'] ?? ''));
        $path = '/api/v1/lyrics/record-info?taskId=' . $taskId;
    } elseif ($api === 'credits') {
        $path = '/api/v1/generate/credit';
    } else {
        http_response_code(404);
        echo json_encode(['code' => 404, 'msg' => 'Unknown local API action'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ch = curl_init($baseUrl . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    $result = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($result === false || $error !== '') {
        http_response_code(502);
        echo json_encode(['code' => 502, 'msg' => 'เชื่อมต่อ Suno API ไม่สำเร็จ', 'error' => $error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code($status > 0 ? $status : 200);
    echo $result;
    exit;
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#16051f">
    <title>Z4BET Suno Studio - เว็บแต่งเพลง AI</title>
    <style>
        :root {
            --bg: #10031a;
            --bg-2: #1d0730;
            --card: rgba(255, 255, 255, .095);
            --card-strong: rgba(255, 255, 255, .15);
            --text: #fff7ff;
            --muted: #cdbdde;
            --pink: #ff4fd8;
            --cyan: #30e3ff;
            --lime: #b8ff5c;
            --orange: #ffb34f;
            --danger: #ff6b7a;
            --shadow: 0 22px 70px rgba(0, 0, 0, .42);
            --radius: 24px;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }
        html { min-height: 100%; background: var(--bg); }
        body {
            min-height: 100vh;
            margin: 0;
            color: var(--text);
            background:
                radial-gradient(circle at 15% 8%, rgba(255, 79, 216, .42), transparent 31%),
                radial-gradient(circle at 88% 4%, rgba(48, 227, 255, .36), transparent 28%),
                radial-gradient(circle at 50% 100%, rgba(184, 255, 92, .16), transparent 28%),
                linear-gradient(160deg, #10031a 0%, #20083a 50%, #080112 100%);
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px);
            background-size: 34px 34px;
            mask-image: linear-gradient(to bottom, rgba(0,0,0,.65), transparent 72%);
        }

        .app {
            width: min(100%, 480px);
            margin: 0 auto;
            padding: max(18px, env(safe-area-inset-top)) 14px max(24px, env(safe-area-inset-bottom));
        }

        .hero {
            position: relative;
            padding: 24px 18px 18px;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 30px;
            background: linear-gradient(145deg, rgba(255,255,255,.16), rgba(255,255,255,.055));
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .hero::after {
            content: "♪";
            position: absolute;
            right: 18px;
            top: 10px;
            font-size: 92px;
            color: rgba(255,255,255,.09);
            transform: rotate(-12deg);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            color: #1b0925;
            font-weight: 800;
            font-size: 12px;
            background: linear-gradient(90deg, var(--lime), var(--cyan));
        }

        h1 {
            position: relative;
            margin: 14px 0 8px;
            font-size: clamp(34px, 11vw, 52px);
            line-height: .92;
            letter-spacing: -2.4px;
        }

        .lead {
            position: relative;
            margin: 0;
            color: var(--muted);
            font-size: 14.5px;
            line-height: 1.65;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 16px;
        }

        .stat {
            padding: 11px 9px;
            border-radius: 18px;
            background: rgba(0,0,0,.22);
            border: 1px solid rgba(255,255,255,.1);
            text-align: center;
        }
        .stat strong { display: block; font-size: 16px; }
        .stat span { color: var(--muted); font-size: 10px; }

        .tabs {
            position: sticky;
            top: 0;
            z-index: 5;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 7px;
            padding: 12px 0;
            backdrop-filter: blur(16px);
        }

        .tab {
            border: 0;
            border-radius: 17px;
            padding: 12px 8px;
            color: var(--muted);
            background: rgba(255,255,255,.09);
            font-weight: 850;
            font-size: 12px;
        }
        .tab.active {
            color: #19051f;
            background: linear-gradient(135deg, var(--pink), var(--orange));
            box-shadow: 0 14px 26px rgba(255, 79, 216, .23);
        }

        .panel { display: none; }
        .panel.active { display: block; }

        .card {
            margin-top: 12px;
            padding: 16px;
            border-radius: var(--radius);
            background: var(--card);
            border: 1px solid rgba(255,255,255,.14);
            box-shadow: 0 16px 48px rgba(0,0,0,.28);
            backdrop-filter: blur(18px);
        }

        .card h2, .card h3 {
            margin: 0 0 12px;
            letter-spacing: -.4px;
        }
        .card h2 { font-size: 22px; }
        .card h3 { font-size: 17px; }

        .hint {
            margin: -4px 0 14px;
            color: var(--muted);
            font-size: 12.5px;
            line-height: 1.55;
        }

        label {
            display: block;
            margin: 13px 0 7px;
            font-size: 12px;
            color: #f9eaff;
            font-weight: 850;
        }

        input, textarea, select {
            width: 100%;
            min-height: 48px;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 16px;
            padding: 13px 14px;
            color: var(--text);
            background: rgba(0,0,0,.28);
            outline: none;
            font: inherit;
        }
        textarea { resize: vertical; min-height: 132px; line-height: 1.5; }
        input:focus, textarea:focus, select:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 4px rgba(48,227,255,.13);
        }
        select option { background: #16051f; color: white; }

        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .switches { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .switch {
            min-height: 56px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border-radius: 18px;
            background: rgba(0,0,0,.22);
            border: 1px solid rgba(255,255,255,.13);
        }
        .switch input { width: 20px; min-height: 20px; accent-color: var(--pink); }
        .switch span { font-size: 12px; font-weight: 800; }

        .counter {
            margin-top: 6px;
            color: var(--muted);
            font-size: 11px;
            text-align: right;
        }
        .counter.bad { color: var(--danger); font-weight: 850; }

        .btn {
            width: 100%;
            min-height: 54px;
            margin-top: 14px;
            border: 0;
            border-radius: 18px;
            padding: 14px 16px;
            color: #17041f;
            background: linear-gradient(135deg, var(--pink), var(--orange));
            font-size: 15px;
            font-weight: 950;
            letter-spacing: -.2px;
            box-shadow: 0 16px 34px rgba(255,79,216,.22);
        }
        .btn.secondary { background: linear-gradient(135deg, var(--cyan), var(--lime)); }
        .btn.ghost {
            color: var(--text);
            background: rgba(255,255,255,.11);
            border: 1px solid rgba(255,255,255,.13);
            box-shadow: none;
        }
        .btn:disabled { opacity: .58; filter: grayscale(.4); }

        .chips { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0 4px; }
        .chip {
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 999px;
            padding: 8px 11px;
            color: var(--muted);
            background: rgba(0,0,0,.2);
            font-size: 11px;
            font-weight: 800;
        }

        .range-line { display: grid; grid-template-columns: 1fr 48px; align-items: center; gap: 10px; }
        input[type="range"] { padding: 0; min-height: 34px; accent-color: var(--cyan); }
        .range-line output { text-align: right; color: var(--cyan); font-weight: 900; }

        .status {
            margin-top: 14px;
            padding: 13px;
            border-radius: 18px;
            color: var(--muted);
            background: rgba(0,0,0,.24);
            border: 1px solid rgba(255,255,255,.12);
            font-size: 12px;
            line-height: 1.55;
            white-space: pre-wrap;
        }
        .status.ok { color: #dfffd2; border-color: rgba(184,255,92,.3); }
        .status.err { color: #ffd1d8; border-color: rgba(255,107,122,.4); }

        .tracks { display: grid; gap: 12px; margin-top: 14px; }
        .track {
            border-radius: 20px;
            overflow: hidden;
            background: rgba(0,0,0,.25);
            border: 1px solid rgba(255,255,255,.12);
        }
        .track img { width: 100%; aspect-ratio: 1.4 / 1; object-fit: cover; display: block; background: #210933; }
        .track-body { padding: 12px; }
        .track h4 { margin: 0 0 6px; font-size: 16px; }
        .track p { margin: 0 0 9px; color: var(--muted); font-size: 12px; line-height: 1.5; }
        audio { width: 100%; height: 42px; }
        .track-links { display: flex; gap: 8px; margin-top: 10px; }
        .track-links a {
            flex: 1;
            padding: 9px;
            border-radius: 12px;
            color: #16051f;
            background: var(--cyan);
            text-align: center;
            text-decoration: none;
            font-size: 12px;
            font-weight: 900;
        }

        .lyrics-card {
            margin-top: 12px;
            padding: 13px;
            border-radius: 18px;
            background: rgba(0,0,0,.25);
            border: 1px solid rgba(255,255,255,.12);
        }
        .lyrics-card pre {
            margin: 8px 0 0;
            white-space: pre-wrap;
            color: #fff7ff;
            font: 13px/1.55 ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .doc-list { display: grid; gap: 9px; }
        .doc-item {
            padding: 12px;
            border-radius: 18px;
            background: rgba(0,0,0,.2);
            border: 1px solid rgba(255,255,255,.1);
        }
        .doc-item strong { display: block; margin-bottom: 4px; }
        .doc-item span { color: var(--muted); font-size: 12px; line-height: 1.5; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: var(--cyan); }

        .bottom-space { height: 20px; }
        @media (max-width: 360px) {
            .app { padding-inline: 10px; }
            .row, .switches { grid-template-columns: 1fr; }
            .stats { grid-template-columns: 1fr; }
            h1 { font-size: 32px; }
        }
    </style>
</head>
<body>
    <main class="app">
        <section class="hero">
            <span class="badge">🎧 Suno API Mobile Studio</span>
            <h1>แต่งเพลง AI<br>จบในมือถือ</h1>
            <p class="lead">สร้างเนื้อเพลง สั่งทำเพลง 2 เวอร์ชัน ตรวจเครดิต และติดตามสถานะจาก Suno API ตามเอกสารล่าสุด พร้อมฟอร์มกันพลาดเรื่อง required fields และ character limit</p>
            <div class="stats">
                <div class="stat"><strong>2</strong><span>เพลงต่อคำสั่ง</span></div>
                <div class="stat"><strong>20-40s</strong><span>stream URL</span></div>
                <div class="stat"><strong>15 วัน</strong><span>เก็บไฟล์</span></div>
            </div>
        </section>

        <nav class="tabs" aria-label="เครื่องมือแต่งเพลง">
            <button class="tab active" data-tab="compose">สร้างเพลง</button>
            <button class="tab" data-tab="lyrics">แต่งเนื้อ</button>
            <button class="tab" data-tab="docs">คู่มือ</button>
        </nav>

        <section id="compose" class="panel active">
            <div class="card">
                <h2>🎼 ตั้งค่าเพลง</h2>
                <p class="hint">API key จะถูกส่งผ่านเซิร์ฟเวอร์หน้านี้เท่านั้น ไม่แสดงใน URL และสามารถตรวจเครดิตก่อนเริ่มงานได้</p>

                <label for="apiKey">Suno API Key</label>
                <input id="apiKey" type="password" autocomplete="off" placeholder="sk-... หรือ token จาก Dashboard">
                <button class="btn secondary" id="creditBtn" type="button">ตรวจเครดิตคงเหลือ</button>
                <div id="creditStatus" class="status">ยังไม่ได้ตรวจเครดิต</div>

                <div class="switches">
                    <label class="switch"><input id="customMode" type="checkbox" checked><span>Custom Mode</span></label>
                    <label class="switch"><input id="instrumental" type="checkbox"><span>Instrumental ไม่มีร้อง</span></label>
                </div>

                <div class="row">
                    <div>
                        <label for="model">โมเดล</label>
                        <select id="model">
                            <option value="V5_5">V5_5 - Custom Models</option>
                            <option value="V5" selected>V5 - เร็วและ expressive</option>
                            <option value="V4_5PLUS">V4_5PLUS - rich, 8 นาที</option>
                            <option value="V4_5ALL">V4_5ALL - structure ดี</option>
                            <option value="V4_5">V4_5 - blend genre</option>
                            <option value="V4">V4 - คุณภาพดี 4 นาที</option>
                        </select>
                    </div>
                    <div>
                        <label for="vocalGender">เสียงร้อง</label>
                        <select id="vocalGender">
                            <option value="">ไม่ระบุ</option>
                            <option value="m">ชาย</option>
                            <option value="f">หญิง</option>
                        </select>
                    </div>
                </div>

                <label for="title">ชื่อเพลง (Custom Mode สูงสุด 80 ตัวอักษร)</label>
                <input id="title" maxlength="80" placeholder="เช่น แสงสุดท้ายในเมืองใหญ่">
                <div class="counter" data-count="title"></div>

                <label for="style">แนวเพลง / Style</label>
                <input id="style" placeholder="เช่น Thai pop, R&B, emotional piano, cinematic drums">
                <div class="chips" aria-label="ตัวอย่างแนวเพลง">
                    <button class="chip" type="button" data-style="Thai pop, warm synth, catchy hook">Thai Pop</button>
                    <button class="chip" type="button" data-style="Lo-fi hip hop, mellow piano, soft drums">Lo-fi</button>
                    <button class="chip" type="button" data-style="EDM festival, big drop, energetic bass">EDM</button>
                    <button class="chip" type="button" data-style="ลูกทุ่งร่วมสมัย, อบอุ่น, กีตาร์โปร่ง">ลูกทุ่ง</button>
                </div>
                <div class="counter" data-count="style"></div>

                <label for="prompt">Prompt / เนื้อเพลง</label>
                <textarea id="prompt" placeholder="Non-custom: บอกไอเดียเพลงให้ AI แต่งเนื้อให้เอง&#10;Custom + มีเสียงร้อง: ใส่เนื้อเพลงจริง เช่น [Verse], [Chorus]"></textarea>
                <div class="counter" data-count="prompt"></div>

                <label for="negativeTags">สิ่งที่ไม่ต้องการ (Negative tags)</label>
                <input id="negativeTags" placeholder="เช่น heavy metal, distorted vocal, noisy drums">

                <label for="callbackUrl">Callback URL (ต้องเป็น URL สาธารณะตามเอกสาร)</label>
                <input id="callbackUrl" type="url" placeholder="https://your-domain.com/suno-callback">

                <details class="card" style="margin-inline:-2px; padding:12px;">
                    <summary style="font-weight:900; cursor:pointer;">ตัวเลือกขั้นสูง: Persona และ weights</summary>
                    <label for="personaId">Persona ID (เฉพาะ Custom Mode)</label>
                    <input id="personaId" placeholder="persona_123">
                    <label for="personaModel">Persona Model</label>
                    <select id="personaModel">
                        <option value="">ไม่ระบุ</option>
                        <option value="style_persona">style_persona</option>
                        <option value="voice_persona">voice_persona</option>
                    </select>
                    <label>Style Weight</label><div class="range-line"><input id="styleWeight" type="range" min="0" max="1" step="0.01" value="0.65"><output>0.65</output></div>
                    <label>Weirdness Constraint</label><div class="range-line"><input id="weirdnessConstraint" type="range" min="0" max="1" step="0.01" value="0.65"><output>0.65</output></div>
                    <label>Audio Weight</label><div class="range-line"><input id="audioWeight" type="range" min="0" max="1" step="0.01" value="0.65"><output>0.65</output></div>
                </details>

                <button class="btn" id="generateMusicBtn" type="button">🚀 สร้างเพลงด้วย Suno</button>
                <div id="musicStatus" class="status">พร้อมสร้างเพลง</div>
            </div>

            <div class="card">
                <h3>🔎 ติดตามสถานะเพลง</h3>
                <p class="hint">ใช้ taskId จากการสร้างเพลง หรือวาง taskId เดิมเพื่อตรวจผลลัพธ์ เพลงจะมี audio, stream, cover และ metadata เมื่อสำเร็จ</p>
                <label for="musicTaskId">Music Task ID</label>
                <input id="musicTaskId" placeholder="5c79****be8e">
                <button class="btn ghost" id="pollMusicBtn" type="button">ตรวจสถานะเพลง</button>
                <div id="tracks" class="tracks"></div>
            </div>
        </section>

        <section id="lyrics" class="panel">
            <div class="card">
                <h2>✍️ แต่งเนื้อเพลงก่อน</h2>
                <p class="hint">Generate Lyrics ใช้ prompt สูงสุด 200 ตัวอักษร และผลลัพธ์นำไปใส่ Generate Music แบบ Custom Mode ได้</p>
                <label for="lyricsPrompt">โจทย์เนื้อเพลง</label>
                <textarea id="lyricsPrompt" maxlength="200" placeholder="เช่น เพลงรักกลางคืนในกรุงเทพ โทนเหงาแต่มีความหวัง มี Verse และ Chorus"></textarea>
                <div class="counter" data-count="lyricsPrompt"></div>
                <label for="lyricsCallbackUrl">Lyrics Callback URL</label>
                <input id="lyricsCallbackUrl" type="url" placeholder="https://your-domain.com/lyrics-callback">
                <button class="btn" id="generateLyricsBtn" type="button">สร้างเนื้อเพลง</button>
                <div id="lyricsStatus" class="status">พร้อมแต่งเนื้อเพลง</div>
            </div>

            <div class="card">
                <h3>🔎 ติดตามสถานะเนื้อเพลง</h3>
                <label for="lyricsTaskId">Lyrics Task ID</label>
                <input id="lyricsTaskId" placeholder="11dc****8b0f">
                <button class="btn ghost" id="pollLyricsBtn" type="button">ตรวจสถานะเนื้อเพลง</button>
                <div id="lyricsResults"></div>
            </div>
        </section>

        <section id="docs" class="panel">
            <div class="card">
                <h2>📚 สรุปตามเอกสาร Suno API</h2>
                <div class="doc-list">
                    <div class="doc-item"><strong>Authentication</strong><span>ทุก endpoint ต้องส่ง <span class="mono">Authorization: Bearer YOUR_API_KEY</span> และควรเก็บ key ให้ปลอดภัย</span></div>
                    <div class="doc-item"><strong>Generate Music</strong><span>POST <span class="mono">/api/v1/generate</span> ส่ง customMode, instrumental, model, callBackUrl และ field ตาม mode ระบบคืน taskId และสร้างเพลง 2 เพลงต่อ request</span></div>
                    <div class="doc-item"><strong>Custom Mode</strong><span>ถ้า instrumental=true ต้องมี style + title; ถ้า instrumental=false ต้องมี style + title + prompt โดย prompt จะถูกใช้เป็นเนื้อร้องจริง</span></div>
                    <div class="doc-item"><strong>Non-custom Mode</strong><span>ต้องมี prompt เท่านั้น จำกัด 500 ตัวอักษร และควรเว้น style/title/advanced fields ว่างเพื่อให้ AI แต่งเพลงจากไอเดีย</span></div>
                    <div class="doc-item"><strong>Character limits</strong><span>V4 prompt 3000, style 200; V4_5/V4_5PLUS/V4_5ALL/V5/V5_5 prompt 5000, style 1000; title 80; lyrics prompt 200</span></div>
                    <div class="doc-item"><strong>Callbacks</strong><span>Music callback มี stage text, first, complete, error; callback ใช้ POST application/json timeout 15 วินาที</span></div>
                    <div class="doc-item"><strong>Polling</strong><span>ใช้ <span class="mono">/api/v1/generate/record-info?taskId=...</span> หรือ <span class="mono">/api/v1/lyrics/record-info?taskId=...</span> ถ้าไม่รอ callback</span></div>
                    <div class="doc-item"><strong>Status</strong><span>Music: PENDING, TEXT_SUCCESS, FIRST_SUCCESS, SUCCESS และ error states เช่น GENERATE_AUDIO_FAILED หรือ SENSITIVE_WORD_ERROR</span></div>
                    <div class="doc-item"><strong>Credits</strong><span>GET <span class="mono">/api/v1/generate/credit</span> คืนเครดิตคงเหลือ แนะนำตรวจเครดิตก่อนเริ่มงานเพื่อเลี่ยง 429</span></div>
                </div>
            </div>
        </section>
        <div class="bottom-space"></div>
    </main>

    <script>
        const $ = (id) => document.getElementById(id);
        const fields = ['apiKey','customMode','instrumental','model','vocalGender','title','style','prompt','negativeTags','callbackUrl','personaId','personaModel','styleWeight','weirdnessConstraint','audioWeight','lyricsPrompt','lyricsCallbackUrl'];
        const storageKey = 'z4bet-suno-studio-v1';
        let pollTimer = null;

        function saveState() {
            const data = {};
            fields.forEach((id) => {
                const el = $(id);
                if (!el) return;
                data[id] = el.type === 'checkbox' ? el.checked : el.value;
            });
            localStorage.setItem(storageKey, JSON.stringify(data));
        }

        function loadState() {
            try {
                const data = JSON.parse(localStorage.getItem(storageKey) || '{}');
                fields.forEach((id) => {
                    const el = $(id);
                    if (!el || data[id] === undefined) return;
                    if (el.type === 'checkbox') el.checked = Boolean(data[id]);
                    else el.value = data[id];
                });
            } catch (_) {}
            updateAllCounters();
            updateRangeOutputs();
        }

        function setStatus(id, text, type = '') {
            const el = $(id);
            el.textContent = text;
            el.className = 'status ' + type;
        }

        async function callLocalApi(action, body) {
            const res = await fetch(`?api=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            const text = await res.text();
            let json;
            try { json = JSON.parse(text); } catch (_) { json = { code: res.status, msg: text || 'Invalid JSON response' }; }
            if (!res.ok || (json.code && Number(json.code) !== 200)) {
                throw new Error(json.msg || json.error || `HTTP ${res.status}`);
            }
            return json;
        }

        function modelLimits() {
            const model = $('model').value;
            const isV4 = model === 'V4';
            return {
                prompt: isV4 ? 3000 : 5000,
                style: isV4 ? 200 : 1000,
                title: 80,
                nonCustomPrompt: 500,
                lyricsPrompt: 200,
            };
        }

        function updateCounter(inputId, max) {
            const el = $(inputId);
            const counter = document.querySelector(`[data-count="${inputId}"]`);
            if (!el || !counter) return;
            counter.textContent = `${el.value.length}/${max} ตัวอักษร`;
            counter.classList.toggle('bad', el.value.length > max);
        }

        function updateAllCounters() {
            const limits = modelLimits();
            const custom = $('customMode').checked;
            updateCounter('title', limits.title);
            updateCounter('style', custom ? limits.style : 0);
            updateCounter('prompt', custom ? limits.prompt : limits.nonCustomPrompt);
            updateCounter('lyricsPrompt', limits.lyricsPrompt);
        }

        function updateRangeOutputs() {
            ['styleWeight','weirdnessConstraint','audioWeight'].forEach((id) => {
                const el = $(id);
                if (el) el.nextElementSibling.textContent = Number(el.value).toFixed(2);
            });
        }

        function validateUrl(value, label) {
            if (!value) return `${label} จำเป็นต้องมีตามเอกสาร`;
            try { new URL(value); return ''; } catch (_) { return `${label} ต้องเป็น URL ที่ถูกต้อง`; }
        }

        function buildMusicPayload() {
            const customMode = $('customMode').checked;
            const instrumental = $('instrumental').checked;
            const limits = modelLimits();
            const prompt = $('prompt').value.trim();
            const style = $('style').value.trim();
            const title = $('title').value.trim();
            const callbackUrl = $('callbackUrl').value.trim();
            const errors = [];

            if (!$('apiKey').value.trim()) errors.push('กรุณาใส่ Suno API Key');
            const callbackError = validateUrl(callbackUrl, 'Callback URL');
            if (callbackError) errors.push(callbackError);

            if (customMode) {
                if (!style) errors.push('Custom Mode ต้องมี style');
                if (!title) errors.push('Custom Mode ต้องมี title');
                if (!instrumental && !prompt) errors.push('Custom Mode แบบมีเสียงร้องต้องมี prompt/เนื้อเพลง');
                if (style.length > limits.style) errors.push(`style ยาวเกิน ${limits.style} ตัวอักษร`);
                if (title.length > limits.title) errors.push('title ยาวเกิน 80 ตัวอักษร');
                if (prompt.length > limits.prompt) errors.push(`prompt ยาวเกิน ${limits.prompt} ตัวอักษรสำหรับโมเดลนี้`);
            } else {
                if (!prompt) errors.push('Non-custom Mode ต้องมี prompt');
                if (prompt.length > limits.nonCustomPrompt) errors.push('Non-custom Mode จำกัด prompt 500 ตัวอักษร');
            }

            if (errors.length) throw new Error(errors.join('\n'));

            const payload = { customMode, instrumental, model: $('model').value, callBackUrl: callbackUrl };
            if (customMode) {
                payload.style = style;
                payload.title = title;
                if (prompt) payload.prompt = prompt;
                const optional = ['negativeTags','personaId','personaModel','vocalGender'];
                optional.forEach((id) => { if ($(id).value.trim()) payload[id] = $(id).value.trim(); });
                payload.styleWeight = Number($('styleWeight').value);
                payload.weirdnessConstraint = Number($('weirdnessConstraint').value);
                payload.audioWeight = Number($('audioWeight').value);
            } else {
                payload.prompt = prompt;
            }
            return payload;
        }

        function renderTracks(json) {
            const container = $('tracks');
            const data = json?.data;
            const status = data?.status || 'UNKNOWN';
            const response = data?.response || {};
            const tracks = response.sunoData || response.data || [];
            setStatus('musicStatus', `สถานะ: ${status}\nTask ID: ${data?.taskId || $('musicTaskId').value}\n${data?.errorMessage || json.msg || ''}`, status === 'SUCCESS' ? 'ok' : '');
            if (!tracks.length) {
                container.innerHTML = `<div class="status">ยังไม่มีไฟล์เพลง อาจอยู่ระหว่าง ${status}</div>`;
                return status;
            }
            container.innerHTML = tracks.map((track, index) => {
                const title = track.title || `เพลงเวอร์ชัน ${index + 1}`;
                const image = track.imageUrl || track.image_url || '';
                const audio = track.audioUrl || track.audio_url || track.streamAudioUrl || track.stream_audio_url || '';
                const stream = track.streamAudioUrl || track.stream_audio_url || '';
                const tags = track.tags || track.modelName || track.model_name || '';
                const duration = track.duration ? `${Math.round(track.duration)} วินาที` : '';
                return `<article class="track">
                    ${image ? `<img src="${escapeHtml(image)}" alt="ปกเพลง ${escapeHtml(title)}" loading="lazy">` : ''}
                    <div class="track-body">
                        <h4>${escapeHtml(title)}</h4>
                        <p>${escapeHtml([tags, duration].filter(Boolean).join(' • '))}</p>
                        ${audio ? `<audio controls src="${escapeHtml(audio)}"></audio>` : '<p>กำลังรอ audio URL</p>'}
                        <div class="track-links">
                            ${audio ? `<a href="${escapeHtml(audio)}" target="_blank" rel="noopener">ดาวน์โหลด</a>` : ''}
                            ${stream ? `<a href="${escapeHtml(stream)}" target="_blank" rel="noopener">Stream</a>` : ''}
                        </div>
                    </div>
                </article>`;
            }).join('');
            return status;
        }

        function renderLyrics(json) {
            const container = $('lyricsResults');
            const data = json?.data;
            const status = data?.status || 'UNKNOWN';
            const items = data?.response?.data || [];
            setStatus('lyricsStatus', `สถานะ: ${status}\nTask ID: ${data?.taskId || $('lyricsTaskId').value}\n${data?.errorMessage || json.msg || ''}`, status === 'SUCCESS' ? 'ok' : '');
            if (!items.length) {
                container.innerHTML = `<div class="status">ยังไม่มีเนื้อเพลง อาจอยู่ระหว่าง ${status}</div>`;
                return;
            }
            container.innerHTML = items.map((item, index) => `<article class="lyrics-card">
                <strong>${escapeHtml(item.title || `ตัวเลือกที่ ${index + 1}`)}</strong>
                <pre>${escapeHtml(item.text || '')}</pre>
                <button class="btn secondary use-lyrics" type="button" data-title="${escapeHtml(item.title || '')}" data-text="${escapeHtml(item.text || '')}">ใช้เนื้อเพลงนี้ในหน้าสร้างเพลง</button>
            </article>`).join('');
            document.querySelectorAll('.use-lyrics').forEach((btn) => btn.addEventListener('click', () => {
                $('customMode').checked = true;
                $('instrumental').checked = false;
                $('prompt').value = btn.dataset.text;
                if (btn.dataset.title) $('title').value = btn.dataset.title.slice(0, 80);
                document.querySelector('[data-tab="compose"]').click();
                updateAllCounters();
                saveState();
            }));
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
        }

        async function pollMusic(manual = true) {
            const taskId = $('musicTaskId').value.trim();
            if (!taskId) throw new Error('กรุณาใส่ Music Task ID');
            if (manual) setStatus('musicStatus', 'กำลังตรวจสถานะเพลง...');
            const json = await callLocalApi('music-status', { apiKey: $('apiKey').value.trim(), taskId });
            const status = renderTracks(json);
            if (status === 'SUCCESS' || status.includes('FAILED') || status.includes('ERROR')) {
                clearInterval(pollTimer);
            }
        }

        async function pollLyrics() {
            const taskId = $('lyricsTaskId').value.trim();
            if (!taskId) throw new Error('กรุณาใส่ Lyrics Task ID');
            setStatus('lyricsStatus', 'กำลังตรวจสถานะเนื้อเพลง...');
            const json = await callLocalApi('lyrics-status', { apiKey: $('apiKey').value.trim(), taskId });
            renderLyrics(json);
        }

        document.querySelectorAll('.tab').forEach((btn) => btn.addEventListener('click', () => {
            document.querySelectorAll('.tab,.panel').forEach((el) => el.classList.remove('active'));
            btn.classList.add('active');
            $(btn.dataset.tab).classList.add('active');
        }));

        document.querySelectorAll('.chip').forEach((chip) => chip.addEventListener('click', () => {
            $('style').value = chip.dataset.style;
            updateAllCounters();
            saveState();
        }));

        document.querySelectorAll('input, textarea, select').forEach((el) => {
            el.addEventListener('input', () => { updateAllCounters(); updateRangeOutputs(); saveState(); });
            el.addEventListener('change', () => { updateAllCounters(); updateRangeOutputs(); saveState(); });
        });

        $('creditBtn').addEventListener('click', async () => {
            try {
                setStatus('creditStatus', 'กำลังตรวจเครดิต...');
                const json = await callLocalApi('credits', { apiKey: $('apiKey').value.trim() });
                setStatus('creditStatus', `เครดิตคงเหลือ: ${json.data}`, 'ok');
            } catch (err) { setStatus('creditStatus', err.message, 'err'); }
        });

        $('generateMusicBtn').addEventListener('click', async () => {
            try {
                const payload = buildMusicPayload();
                setStatus('musicStatus', 'กำลังส่งคำสั่งสร้างเพลง...');
                const json = await callLocalApi('generate-music', { apiKey: $('apiKey').value.trim(), payload });
                const taskId = json?.data?.taskId || '';
                $('musicTaskId').value = taskId;
                setStatus('musicStatus', `ส่งสำเร็จ: ${json.msg}\nTask ID: ${taskId}\nระบบจะเริ่มตรวจสถานะอัตโนมัติทุก 12 วินาที`, 'ok');
                saveState();
                clearInterval(pollTimer);
                pollTimer = setInterval(() => pollMusic(false).catch((err) => setStatus('musicStatus', err.message, 'err')), 12000);
            } catch (err) { setStatus('musicStatus', err.message, 'err'); }
        });

        $('pollMusicBtn').addEventListener('click', () => pollMusic(true).catch((err) => setStatus('musicStatus', err.message, 'err')));

        $('generateLyricsBtn').addEventListener('click', async () => {
            try {
                const prompt = $('lyricsPrompt').value.trim();
                const callbackUrl = $('lyricsCallbackUrl').value.trim();
                const errors = [];
                if (!$('apiKey').value.trim()) errors.push('กรุณาใส่ Suno API Key');
                if (!prompt) errors.push('กรุณาใส่โจทย์เนื้อเพลง');
                if (prompt.length > 200) errors.push('Generate Lyrics จำกัด prompt 200 ตัวอักษร');
                const callbackError = validateUrl(callbackUrl, 'Lyrics Callback URL');
                if (callbackError) errors.push(callbackError);
                if (errors.length) throw new Error(errors.join('\n'));

                setStatus('lyricsStatus', 'กำลังส่งคำสั่งแต่งเนื้อเพลง...');
                const json = await callLocalApi('generate-lyrics', { apiKey: $('apiKey').value.trim(), payload: { prompt, callBackUrl: callbackUrl } });
                const taskId = json?.data?.taskId || '';
                $('lyricsTaskId').value = taskId;
                setStatus('lyricsStatus', `ส่งสำเร็จ: ${json.msg}\nTask ID: ${taskId}`, 'ok');
                saveState();
            } catch (err) { setStatus('lyricsStatus', err.message, 'err'); }
        });

        $('pollLyricsBtn').addEventListener('click', () => pollLyrics().catch((err) => setStatus('lyricsStatus', err.message, 'err')));

        loadState();
    </script>
</body>
</html>
