# 📱 DAL TOKKI CAFE - Website + APK Download

## 📋 Setup Instructions

### 1. Place Your APK File Here

**Requirements:**
- File name: `app-release.apk`
- Location: Same folder as `index.html` (this folder)
- Size: Should be under 100MB for Vercel

**Current folder structure:**
```
website/
├── index.html          ✅
├── app-release.apk     ⬅️ PUT YOUR APK FILE HERE
├── logo.png
├── dal tokki cafe.jpg
└── README.md
```

---

## 🚀 Deploy to Vercel

### Option 1: Vercel CLI (Recommended)

```bash
# Install Vercel CLI globally
npm i -g vercel

# Navigate to website folder
cd website

# Login to Vercel
vercel login

# Deploy!
vercel deploy

# Follow prompts:
# - Y to Set up and deploy
# - N to Link to existing project (first time)
# - Project name: daltokki-cafe
# - Directory: ./
# - Override settings: N
```

### Option 2: Via GitHub

```bash
# Initialize Git repository
git init
git add .
git commit -m "DAL TOKKI website with APK download"

# Push to GitHub
git remote add origin https://github.com/YOUR_USERNAME/daltokki-cafe.git
git push -u origin main

# Then import on Vercel Dashboard
# Go to: https://vercel.com/new
# Select your repository
# Click "Deploy"
```

---

## ✅ Features

### Website Features:
- ✅ Operating Hours: Monday CLOSED, Tue-Sun 3pm-10pm
- ✅ APK Download button with auto-download
- ✅ Modern responsive design
- ✅ Korean cafe menu display
- ✅ Social media links
- ✅ Contact information

### APK Download:
- ✅ Click "Download Now" → APK downloads automatically
- ✅ File renamed to: `DAL_TOKKI_CAFE.apk`
- ✅ Works on all browsers
- ✅ Mobile-friendly

---

## 📝 Important Notes

1. **APK File Name:**
   - Must be: `app-release.apk`
   - Don't rename it
   - Put it in this `website/` folder

2. **Vercel Deployment:**
   - Deploy entire `website/` folder
   - Vercel serves files from root directory
   - APK will be accessible at: `https://your-domain.vercel.app/app-release.apk`

3. **File Size:**
   - Keep APK under 100MB
   - Vercel has 100MB limit for individual files
   - If larger, use external storage (Dropbox, Google Drive, etc.)

---

## 🎯 After Deployment

Your website will be live at:
- **URL:** `https://daltokki-cafe.vercel.app`
- **APK:** `https://daltokki-cafe.vercel.app/app-release.apk`

---

## 🆘 Troubleshooting

### Problem: APK not downloading

**Check:**
1. APK file exists in this folder?
2. File name is exactly `app-release.apk`?
3. Deployed to Vercel successfully?

**Fix:**
```bash
# Verify APK is here
ls -la app-release.apk

# Redeploy
vercel deploy --force
```

### Problem: 404 on APK link

**Fix:**
1. Make sure APK is in the same folder as `index.html`
2. Don't put it in subfolders
3. Redeploy to Vercel

---

## 📞 Need Help?

Check `VERCEL_DEPLOYMENT_GUIDE.md` in the root folder for detailed instructions.

