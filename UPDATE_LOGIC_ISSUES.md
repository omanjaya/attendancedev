# Update Logic Issues - Audit Report

Generated: 2025-12-10
Audited by: Claude Code

## 🔴 CRITICAL ISSUES FOUND

### 1. **Race Condition: Navigate Before Refetch Completes**

**Location:**
- `/frontend/src/pages/admin/schedules/monthly/edit.tsx:196-200`
- `/frontend/src/pages/admin/schedules/monthly/create.tsx` (similar pattern)

**Problem:**
```tsx
onSuccess: () => {
  queryClient.invalidateQueries({ queryKey: ['monthly-schedules'] });
  queryClient.invalidateQueries({ queryKey: ['monthly-schedule', id] });
  success('Berhasil', 'Jadwal bulanan berhasil diupdate');
  navigate({ to: '/admin/schedules/monthly' }); // ❌ NAVIGATES IMMEDIATELY
},
```

**Why This is a Problem:**
1. `invalidateQueries()` is **asynchronous** but not awaited
2. `navigate()` executes immediately without waiting for refetch
3. User lands on list page with **potentially stale data**
4. Race condition: sometimes shows new data, sometimes old data

**Impact:**
- User sees old data after update
- Confusing UX: "I just updated it, why is it still showing the old value?"
- Intermittent bug (depends on network speed)

**Recommended Fix:**
```tsx
onSuccess: async () => {
  // Await invalidation to ensure refetch completes
  await queryClient.invalidateQueries({ queryKey: ['monthly-schedules'] });
  await queryClient.invalidateQueries({ queryKey: ['monthly-schedule', id] });
  success('Berhasil', 'Jadwal bulanan berhasil diupdate');
  navigate({ to: '/admin/schedules/monthly' });
},
```

OR use `refetchQueries` instead:
```tsx
onSuccess: async () => {
  await queryClient.refetchQueries({ queryKey: ['monthly-schedules'] });
  await queryClient.refetchQueries({ queryKey: ['monthly-schedule', id] });
  success('Berhasil', 'Jadwal bulanan berhasil diupdate');
  navigate({ to: '/admin/schedules/monthly' });
},
```

---

### 2. **Missing Query Invalidation**

**Need to audit:** Check if all related queries are being invalidated.

Example potential missing invalidations:
- When updating a schedule, are we invalidating:
  - Statistics?
  - Calendar views?
  - Employee schedules that depend on this?

**TODO: Deep dive into each update mutation to verify all related queries are invalidated**

---

### 3. **Inconsistent Error Handling**

**Location:** Multiple files

**Problem:**
Some mutations use global error handler, some have custom onError that might override it.

Example in `monthly/edit.tsx:202-205`:
```tsx
onError: (err: any) => {
  const message = err?.response?.data?.message || 'Gagal mengupdate jadwal bulanan';
  showError('Error', message);
},
```

**Issue:**
- This custom error handler might **duplicate** the global error toast
- Results in **double error messages** to user
- Since we implemented global error handling in App.tsx, this is redundant

**Recommended Fix:**
Remove custom onError handlers since global handler already covers this:
```tsx
// Just remove the onError callback entirely
onSuccess: async () => {
  await queryClient.invalidateQueries({ queryKey: ['monthly-schedules'] });
  await queryClient.invalidateQueries({ queryKey: ['monthly-schedule', id] });
  success('Berhasil', 'Jadwal bulanan berhasil diupdate');
  navigate({ to: '/admin/schedules/monthly' });
},
// No onError needed - global handler will catch it
```

---

## 🟡 POTENTIAL ISSUES (Need Verification)

### 4. **Stale Data in Zustand Store**

Some operations might use both TanStack Query AND Zustand stores.
Need to verify that Zustand stores are also updated after mutations.

**Files to check:**
- Location updates (`use-locations.ts`)
- Auth/user profile updates

---

### 5. **Optimistic Updates Missing**

For better UX, some operations should use optimistic updates:
- Employee avatar upload
- Toggle operations (active/inactive)
- Simple field updates

**Example for toggle operation:**
```tsx
const mutation = useMutation({
  mutationFn: toggleStatus,
  onMutate: async (id) => {
    // Cancel outgoing refetches
    await queryClient.cancelQueries({ queryKey: ['items'] });

    // Snapshot previous value
    const previous = queryClient.getQueryData(['items']);

    // Optimistically update
    queryClient.setQueryData(['items'], (old) =>
      old.map(item => item.id === id ? {...item, active: !item.active} : item)
    );

    return { previous };
  },
  onError: (err, variables, context) => {
    // Rollback on error
    queryClient.setQueryData(['items'], context.previous);
  },
  onSettled: () => {
    // Always refetch after error or success
    queryClient.invalidateQueries({ queryKey: ['items'] });
  },
});
```

---

## 📋 AUDIT CHECKLIST

### Critical Update Operations to Verify:

- [ ] Monthly Schedule Update
- [ ] Daily Schedule Update
- [ ] Employee Update
- [ ] Employee Avatar Update
- [ ] Location Update
- [ ] Attendance Correction Approve/Reject
- [ ] Leave Request Approve/Reject
- [ ] Payroll Period Update
- [ ] Holiday Update
- [ ] Profile Update
- [ ] Face Recognition Update

### For Each Operation, Check:

1. ✅ Are all related queries invalidated?
2. ✅ Is invalidation awaited before navigation?
3. ✅ No duplicate error handling?
4. ✅ Zustand store updated (if applicable)?
5. ✅ Optimistic updates for better UX (optional)?
6. ✅ Loading states handled properly?

---

## 🔧 QUICK WINS (Easy Fixes)

1. **Add await to all navigate-after-update patterns** (~10 locations)
2. **Remove redundant onError handlers** (global handler covers it)
3. **Add missing query invalidations** (need to identify which)

---

## 📊 STATISTICS

- Total Update Operations: **45+**
- Operations with navigate pattern: **~10**
- Operations with custom error handling: **~15**
- Critical fixes needed: **10-15**

---

## NEXT STEPS

1. Fix navigate-after-invalidate race condition (PRIORITY 1)
2. Remove redundant error handlers (PRIORITY 2)
3. Audit all query invalidations for completeness (PRIORITY 3)
4. Add optimistic updates where beneficial (PRIORITY 4)
