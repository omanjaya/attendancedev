# 🧪 COMPREHENSIVE TESTING PLAN - All Features + Mobile

**Date:** 2025-12-10
**Purpose:** Verify all update logic fixes work correctly across ALL features and devices
**Status:** 🟡 IN PROGRESS

---

## 📱 TESTING ENVIRONMENTS

### Desktop Testing:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (if macOS)

### Mobile Testing:
- [ ] Chrome Mobile (Android/iOS)
- [ ] Safari Mobile (iOS)
- [ ] Responsive mode di DevTools (375px, 768px, 1024px)

### Network Conditions:
- [ ] Fast (WiFi/4G)
- [ ] Slow 3G (throttled)
- [ ] Offline → Online transition

---

## 🎯 CRITICAL USER FLOWS - PRIORITY 1

### 1. **Attendance Flow (Core Feature)** ⭐ CRITICAL

#### Desktop:
- [ ] Login sebagai employee
- [ ] Check-in dengan face recognition
  - [ ] Verify attendance data langsung muncul di dashboard
  - [ ] Verify tidak ada stale data
- [ ] Check-out
  - [ ] Verify status berubah dari "checked-in" ke "completed"
  - [ ] Verify total working hours ter-calculate
- [ ] View attendance history
  - [ ] Verify data complete dan sorted correctly

#### Mobile:
- [ ] Same flow di mobile Chrome
- [ ] Test face capture di mobile camera
- [ ] Verify responsive layout
- [ ] Test portrait & landscape mode

**Expected Result:**
✅ Attendance data langsung muncul tanpa refresh
✅ Face recognition works di mobile camera
✅ No stale data di list/dashboard

---

### 2. **Monthly Schedule Management** ⭐ CRITICAL

#### Desktop:
- [ ] Login sebagai admin
- [ ] Navigate to Monthly Schedules
- [ ] **CREATE** new monthly schedule
  - [ ] Fill form (nama, bulan, tahun, working days)
  - [ ] Submit
  - [ ] **VERIFY:** Navigate ke list → schedule langsung muncul
  - [ ] **VERIFY:** Data yang ditampilkan sesuai yang diinput
- [ ] **EDIT** monthly schedule yang baru dibuat
  - [ ] Ubah nama schedule
  - [ ] Ubah working hours
  - [ ] Submit
  - [ ] **VERIFY:** Navigate ke list → data langsung update
  - [ ] **VERIFY:** Tidak ada data lama yang muncul
- [ ] **ASSIGN** employees to schedule
  - [ ] Pilih multiple employees
  - [ ] Assign ke schedule
  - [ ] **VERIFY:** Employee list langsung update
- [ ] **DELETE** test schedule
  - [ ] Confirm delete
  - [ ] **VERIFY:** Schedule langsung hilang dari list

#### Mobile:
- [ ] Same flow di mobile
- [ ] Test calendar picker di mobile
- [ ] Verify form responsive
- [ ] Test multi-select employees di mobile

**Expected Result:**
✅ Create → langsung muncul di list (no stale)
✅ Edit → data langsung update (no old data)
✅ Assign → employee list fresh
✅ Delete → langsung hilang

---

### 3. **Employee Management** ⭐ CRITICAL

#### Desktop:
- [ ] Navigate to Employees
- [ ] **CREATE** new employee
  - [ ] Fill all required fields
  - [ ] Submit
  - [ ] **VERIFY:** Employee langsung muncul di list
- [ ] **EDIT** employee data
  - [ ] Change name, email, position
  - [ ] Submit
  - [ ] **VERIFY:** Data langsung update di list
  - [ ] **VERIFY:** Detail page juga update
- [ ] **UPLOAD** employee avatar
  - [ ] Select image
  - [ ] Upload
  - [ ] **VERIFY:** Avatar langsung muncul (no refresh needed)
- [ ] **DELETE** avatar
  - [ ] Confirm delete
  - [ ] **VERIFY:** Avatar langsung hilang, kembali ke default

#### Mobile:
- [ ] Same flow di mobile
- [ ] Test image upload dari mobile camera/gallery
- [ ] Verify avatar preview di mobile

**Expected Result:**
✅ Create employee → langsung di list
✅ Edit → data fresh instantly
✅ Upload avatar → langsung muncul
✅ Delete avatar → langsung hilang

---

### 4. **Leave Request Flow** ⭐ CRITICAL

#### Employee Side (Desktop):
- [ ] Login sebagai employee
- [ ] **CREATE** leave request
  - [ ] Select dates, type, reason
  - [ ] Submit
  - [ ] **VERIFY:** Request langsung muncul di "My Requests"
  - [ ] **VERIFY:** Status = "Pending"
- [ ] **CANCEL** leave request
  - [ ] Click cancel
  - [ ] **VERIFY:** Status berubah ke "Cancelled"

#### Admin Side (Desktop):
- [ ] Login sebagai admin
- [ ] Navigate to Leave Requests → Pending
- [ ] **APPROVE** leave request
  - [ ] Add notes (optional)
  - [ ] Approve
  - [ ] **VERIFY:** Status langsung berubah ke "Approved"
  - [ ] **VERIFY:** Request hilang dari "Pending" tab
  - [ ] **VERIFY:** Muncul di "Approved" tab
- [ ] **REJECT** another leave request
  - [ ] Add reason
  - [ ] Reject
  - [ ] **VERIFY:** Status langsung berubah ke "Rejected"

#### Mobile:
- [ ] Employee: Create & view requests di mobile
- [ ] Admin: Approve/reject di mobile

**Expected Result:**
✅ Create → langsung di pending list
✅ Approve → status update instantly, pindah tab
✅ Reject → status update instantly
✅ Cancel → status update instantly

---

### 5. **Payroll Management** ⭐ HIGH PRIORITY

#### Desktop:
- [ ] Login sebagai admin
- [ ] Navigate to Payroll
- [ ] **CREATE** new payroll period
  - [ ] Select month/year
  - [ ] Submit
  - [ ] **VERIFY:** Period langsung muncul
- [ ] **CALCULATE** payroll
  - [ ] Click calculate
  - [ ] **VERIFY:** Data calculated (total, deductions, etc)
- [ ] **UPDATE** employee payroll data
  - [ ] Add bonus/deductions
  - [ ] Submit
  - [ ] **VERIFY:** Total gaji langsung update
- [ ] **APPROVE** payroll
  - [ ] Click approve
  - [ ] **VERIFY:** Status → "Approved"
- [ ] **MARK AS PAID**
  - [ ] Select payment method, date
  - [ ] Submit
  - [ ] **VERIFY:** Status → "Paid"

#### Mobile:
- [ ] View payroll di mobile
- [ ] Test approval flow di mobile

**Expected Result:**
✅ All status changes instant
✅ Calculations update immediately
✅ No stale totals/data

---

## 🔄 SECONDARY FLOWS - PRIORITY 2

### 6. **Attendance Corrections**

- [ ] Employee: Request correction
  - [ ] **VERIFY:** Request langsung muncul
- [ ] Admin: Approve correction
  - [ ] **VERIFY:** Status update, data attendance ter-correct
- [ ] Admin: Reject correction
  - [ ] **VERIFY:** Status update instantly

---

### 7. **Holidays Management**

- [ ] Admin: Create holiday
  - [ ] **VERIFY:** Holiday langsung muncul di calendar
- [ ] Admin: Update holiday
  - [ ] **VERIFY:** Data langsung update
- [ ] Admin: Delete holiday
  - [ ] **VERIFY:** Holiday langsung hilang

---

### 8. **Profile & Settings**

- [ ] User: Update profile
  - [ ] Change name, email, phone
  - [ ] **VERIFY:** Profile data update instantly
- [ ] User: Upload avatar
  - [ ] Upload photo
  - [ ] **VERIFY:** Avatar langsung muncul di header
- [ ] User: Delete avatar
  - [ ] **VERIFY:** Avatar langsung kembali ke default
- [ ] User: Change password
  - [ ] Change password
  - [ ] **VERIFY:** Success message, bisa login dengan password baru

---

### 9. **Face Recognition**

- [ ] Employee: Register face
  - [ ] Capture face photo
  - [ ] **VERIFY:** Face registered, langsung bisa digunakan
- [ ] Employee: Update face
  - [ ] Capture new photo
  - [ ] **VERIFY:** Face data updated
- [ ] Employee: Use face for attendance
  - [ ] Check-in with face
  - [ ] **VERIFY:** Face recognized, attendance recorded

---

### 10. **Reports**

- [ ] Admin: Generate attendance report
  - [ ] Select filters
  - [ ] Generate
  - [ ] **VERIFY:** Report data accurate
- [ ] Admin: Export to Excel
  - [ ] Click export
  - [ ] **VERIFY:** File downloaded
- [ ] Admin: Update report template
  - [ ] Modify template
  - [ ] **VERIFY:** Template updated

---

## 📱 MOBILE-SPECIFIC TESTING

### Responsive Layout Testing:

#### Screen Sizes:
- [ ] **Mobile Portrait** (375px × 667px) - iPhone SE
- [ ] **Mobile Landscape** (667px × 375px)
- [ ] **Tablet Portrait** (768px × 1024px) - iPad
- [ ] **Tablet Landscape** (1024px × 768px)

### Mobile UX Testing:

#### Navigation:
- [ ] Hamburger menu works smoothly
- [ ] Bottom navigation accessible
- [ ] Swipe gestures work (if any)

#### Forms:
- [ ] All form fields accessible
- [ ] Virtual keyboard doesn't hide inputs
- [ ] Date pickers work on mobile
- [ ] File upload from camera/gallery works

#### Touch Interactions:
- [ ] Buttons have adequate tap targets (44px minimum)
- [ ] Scrolling smooth
- [ ] Modal dialogs work
- [ ] Dropdown/select menus work

#### Camera Access:
- [ ] Face recognition camera works
- [ ] Photo upload from camera works
- [ ] Permissions properly requested

---

## 🌐 NETWORK CONDITIONS TESTING

### Slow 3G Testing (CRITICAL!):
- [ ] Throttle network to Slow 3G
- [ ] Test update employee
  - [ ] **VERIFY:** Meskipun lambat, data tetap fresh (tidak stale)
- [ ] Test create schedule
  - [ ] **VERIFY:** Loading state shown
  - [ ] **VERIFY:** After success, data pasti fresh
- [ ] Test approve leave
  - [ ] **VERIFY:** No stale status

**This is the ULTIMATE test for race condition fix!**

---

## 🚨 ERROR SCENARIOS TESTING

### Error Handling:
- [ ] Submit form dengan validation errors
  - [ ] **VERIFY:** Single error toast (not double)
  - [ ] **VERIFY:** Clear error messages
- [ ] Network error (disconnect internet)
  - [ ] **VERIFY:** Proper error message
  - [ ] **VERIFY:** No crash
- [ ] Server error (500)
  - [ ] **VERIFY:** User-friendly error message
  - [ ] **VERIFY:** Single toast (not double)
- [ ] Unauthorized (401)
  - [ ] **VERIFY:** Redirect to login

---

## 📊 PERFORMANCE TESTING

### Load Time:
- [ ] Initial page load < 3s
- [ ] Navigation between pages smooth
- [ ] List loading with pagination works

### Data Refresh:
- [ ] After update, data appears within 1s
- [ ] No visible "flash" of old data
- [ ] Loading states appropriate

---

## ✅ TEST COMPLETION CHECKLIST

### Must Pass (BLOCKER):
- [ ] All CRITICAL flows work on desktop
- [ ] All CRITICAL flows work on mobile
- [ ] Slow 3G test passes (no stale data)
- [ ] No double error toasts
- [ ] No TypeScript errors in console

### Should Pass (HIGH):
- [ ] All SECONDARY flows work
- [ ] Mobile responsive layouts correct
- [ ] Error scenarios handled gracefully

### Nice to Have:
- [ ] Performance metrics good
- [ ] All browsers tested
- [ ] All screen sizes tested

---

## 🐛 BUG REPORTING FORMAT

Jika menemukan bug:

```markdown
**Bug Title:** [Short description]
**Severity:** Critical / High / Medium / Low
**Steps to Reproduce:**
1. Login as X
2. Navigate to Y
3. Click Z
4. ...

**Expected:** What should happen
**Actual:** What actually happened
**Environment:** Desktop/Mobile, Browser, Network condition
**Screenshot/Video:** [If applicable]
```

---

## 📝 TEST EXECUTION NOTES

### Testing Order:
1. **Start with CRITICAL flows on desktop**
2. **Then test CRITICAL flows on mobile**
3. **Then test SECONDARY flows**
4. **Finally test edge cases and error scenarios**

### Time Estimate:
- CRITICAL flows: ~2 hours
- SECONDARY flows: ~1 hour
- Mobile testing: ~1 hour
- Network/Error testing: ~30 mins
- **TOTAL: ~4.5 hours** for comprehensive testing

---

## 🎯 SUCCESS CRITERIA

**Test is successful if:**
✅ **Zero stale data** issues on any update operation
✅ **Single error toast** (no doubles)
✅ **Mobile works** as smoothly as desktop
✅ **Slow network** doesn't cause stale data
✅ **All critical user flows** complete successfully

**Ready for production if:**
✅ All CRITICAL tests pass
✅ All SECONDARY tests pass (or bugs documented)
✅ Mobile responsive works
✅ No blocking bugs

---

**Next Step:** Execute this testing plan systematically and document results! 🚀
