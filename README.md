# LINE-BOT-PHP-Starter + Auto Clip Pipeline

โปรเจกต์นี้เพิ่มโครงระบบ “สร้างคลิปออโต้” ที่ใช้งาน KNP APIs ตาม flow:

1. สร้าง script ผ่าน `POST /v1/chat/completions`
2. สร้างภาพแต่ละฉากผ่าน `POST /v1/images/generations`
3. สร้างเสียงพากย์ผ่าน `POST /v1/audio/speech`
4. สร้างวิดีโอผ่าน `POST /v1/video/create` + polling สถานะ
5. ถ้าวิดีโอไม่ผ่านและเปิด fallback จะคืนผลเป็น image clip อัตโนมัติ

## โครงสร้างหลัก

- `autoclip.php` จุดเริ่มรัน pipeline
- `src/Services/AutoClipPipeline.php` orchestration หลัก
- `src/Services/VideoService.php` จัดการ create task + poll สถานะ โดยกำหนด field path ผ่าน `.env`
- `src/Admin/AdminSettings.php` ค่า admin settings (`auto_video`, `fallback_to_image_clip`, `knp_video_model`)
- `src/Services/ClipPlanner.php` คำนวณจำนวนฉาก (default 8 วินาที/ฉาก)

## ตัวอย่างการคำนวณฉาก

- เสียงพากย์ 3 นาที = 180 วินาที
- 180 / 8 = 22.5 -> ปัดขึ้นเป็น 23 ฉาก

## การตั้งค่า

คัดลอกไฟล์ `.env.example` เป็น `.env` แล้วตั้งค่า:

- `KNP_BASE_URL`, `KNP_API_KEY`
- `KNP_CHAT_MODEL`, `KNP_IMAGE_MODEL`, `KNP_TTS_MODEL`, `KNP_VIDEO_MODEL`
- `AUTO_VIDEO`, `FALLBACK_TO_IMAGE_CLIP`
- ค่าที่เกี่ยวกับ video status field path เช่น `KNP_VIDEO_STATUS_FIELD`, `KNP_VIDEO_URL_FIELD`

## การใช้งาน

```bash
composer dump-autoload
php autoclip.php "หัวข้อที่ต้องการ" 180
```

พารามิเตอร์ที่ 2 คือความยาวเสียงพากย์ (วินาที)
