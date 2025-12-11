import { useState, useMemo } from 'react';
import { Link, useNavigate, useParams } from '@tanstack/react-router';
import {
  ArrowLeft,
  DollarSign,
  TrendingUp,
  TrendingDown,
  Loader2,
  Save,
  Calculator,
  Plus,
  Pencil,
  Trash2,
  Lock,
  Gift,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { toast } from 'sonner';
import {
  useEmployeePayrollDetail,
  usePayrollItemCategories,
  useCreatePayrollItem,
  useUpdatePayrollItem,
  useDeletePayrollItem,
} from '@/hooks/use-payroll';
import type { PayrollItem, PayrollItemInput, PayrollItemType } from '@/lib/api/payroll';

interface ItemFormData {
  type: PayrollItemType;
  category: string;
  description: string;
  amount: number;
  is_taxable: boolean;
  notes: string;
}

const defaultItemForm: ItemFormData = {
  type: 'earning',
  category: 'allowance',
  description: '',
  amount: 0,
  is_taxable: true,
  notes: '',
};

export default function PayrollEditPage() {
  const navigate = useNavigate();
  const params = useParams({
    strict: false,
  }) as {
    periodId: string;
    employeeId: string;
  };

  const [itemDialogOpen, setItemDialogOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<PayrollItem | null>(null);
  const [itemForm, setItemForm] = useState<ItemFormData>(defaultItemForm);
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState<PayrollItem | null>(null);

  const { data: payroll, isLoading, error } = useEmployeePayrollDetail(
    params.periodId,
    params.employeeId
  );

  const { data: categories } = usePayrollItemCategories();

  const createItemMutation = useCreatePayrollItem();
  const updateItemMutation = useUpdatePayrollItem();
  const deleteItemMutation = useDeletePayrollItem();

  // Calculate totals from items
  const totals = useMemo(() => {
    if (!payroll) return { earnings: 0, deductions: 0, bonuses: 0, net: 0 };

    const earnings = payroll.earnings.reduce((sum, item) => sum + Number(item.amount), 0);
    const deductions = payroll.deductions.reduce((sum, item) => sum + Number(item.amount), 0);
    const bonuses = payroll.bonuses.reduce((sum, item) => sum + Number(item.amount), 0);

    return {
      earnings,
      deductions,
      bonuses,
      net: earnings + bonuses - deductions,
    };
  }, [payroll]);

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const openAddItemDialog = (type: PayrollItemType) => {
    const defaultCategory = type === 'earning' ? 'allowance' : type === 'deduction' ? 'other' : 'bonus';
    setEditingItem(null);
    setItemForm({
      ...defaultItemForm,
      type,
      category: defaultCategory,
    });
    setItemDialogOpen(true);
  };

  const openEditItemDialog = (item: PayrollItem) => {
    setEditingItem(item);
    setItemForm({
      type: item.type,
      category: item.category,
      description: item.description,
      amount: Number(item.amount),
      is_taxable: item.is_taxable,
      notes: item.notes || '',
    });
    setItemDialogOpen(true);
  };

  const handleSaveItem = async () => {
    if (!itemForm.description.trim()) {
      toast.error('Deskripsi wajib diisi');
      return;
    }
    if (itemForm.amount <= 0) {
      toast.error('Jumlah harus lebih dari 0');
      return;
    }

    const data: PayrollItemInput = {
      type: itemForm.type,
      category: itemForm.category,
      description: itemForm.description.trim(),
      amount: itemForm.amount,
      is_taxable: itemForm.is_taxable,
      notes: itemForm.notes.trim() || undefined,
    };

    if (editingItem) {
      // Update existing item
      updateItemMutation.mutate(
        {
          payrollId: payroll!.id,
          itemId: editingItem.id,
          periodId: params.periodId,
          employeeId: params.employeeId,
          data,
        },
        {
          onSuccess: () => {
            setItemDialogOpen(false);
            setEditingItem(null);
          },
        }
      );
    } else {
      // Create new item
      createItemMutation.mutate(
        {
          payrollId: payroll!.id,
          periodId: params.periodId,
          employeeId: params.employeeId,
          data,
        },
        {
          onSuccess: () => {
            setItemDialogOpen(false);
          },
        }
      );
    }
  };

  const handleDeleteItem = (item: PayrollItem) => {
    setItemToDelete(item);
    setDeleteConfirmOpen(true);
  };

  const confirmDeleteItem = () => {
    if (!itemToDelete) return;

    deleteItemMutation.mutate(
      {
        payrollId: payroll!.id,
        itemId: itemToDelete.id,
        periodId: params.periodId,
        employeeId: params.employeeId,
      },
      {
        onSuccess: () => {
          setDeleteConfirmOpen(false);
          setItemToDelete(null);
        },
      }
    );
  };

  const getCategoryLabel = (type: PayrollItemType, category: string) => {
    if (!categories) return category;
    const catList = categories[type];
    const found = catList?.find((c) => c.value === category);
    return found?.label || category;
  };

  const renderItemsList = (
    items: PayrollItem[],
    type: PayrollItemType,
    title: string,
    icon: React.ReactNode,
    colorClass: string
  ) => (
    <Card>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <CardTitle className={`flex items-center gap-2 text-lg ${colorClass}`}>
            {icon}
            {title}
          </CardTitle>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => openAddItemDialog(type)}
          >
            <Plus className="h-4 w-4 mr-1" />
            Tambah
          </Button>
        </div>
        <CardDescription>
          {items.length} item - Total: {formatCurrency(items.reduce((sum, i) => sum + Number(i.amount), 0))}
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-2">
        {items.length === 0 ? (
          <p className="text-sm text-muted-foreground text-center py-4">
            Belum ada item {title.toLowerCase()}
          </p>
        ) : (
          items.map((item) => (
            <div
              key={item.id}
              className="flex items-center justify-between p-3 rounded-lg border bg-card hover:bg-accent/50 transition-colors"
            >
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2">
                  <span className="font-medium text-sm truncate">{item.description}</span>
                  {item.is_statutory && (
                    <Badge variant="secondary" className="text-xs">
                      <Lock className="h-3 w-3 mr-1" />
                      Wajib
                    </Badge>
                  )}
                  {item.is_taxable && (
                    <Badge variant="outline" className="text-xs">Kena Pajak</Badge>
                  )}
                </div>
                <p className="text-xs text-muted-foreground mt-0.5">
                  {getCategoryLabel(item.type, item.category)}
                  {item.notes && ` - ${item.notes}`}
                </p>
              </div>
              <div className="flex items-center gap-2">
                <span className={`font-semibold text-sm ${colorClass}`}>
                  {formatCurrency(Number(item.amount))}
                </span>
                {item.can_edit && (
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    onClick={() => openEditItemDialog(item)}
                  >
                    <Pencil className="h-4 w-4" />
                  </Button>
                )}
                {item.can_delete && (
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8 text-destructive hover:text-destructive"
                    onClick={() => handleDeleteItem(item)}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                )}
              </div>
            </div>
          ))
        )}
      </CardContent>
    </Card>
  );

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  if (error || !payroll) {
    return (
      <div className="p-4 sm:p-6 max-w-5xl mx-auto">
        <Link
          to="/admin/payroll"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar payroll
        </Link>
        <Card>
          <CardContent className="p-4 sm:p-6 text-center">
            <p className="text-muted-foreground">
              {error?.message || 'Data payroll tidak ditemukan'}
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="p-4 sm:p-6 max-w-5xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/admin/payroll"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar payroll
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Edit Payroll</h1>
        <div className="flex flex-wrap items-center gap-2 mt-1">
          <p className="text-sm text-muted-foreground">
            {payroll.employee.name}
          </p>
          <Badge variant="outline">{payroll.employee.employee_code}</Badge>
          {payroll.employee.department && (
            <Badge variant="secondary">{payroll.employee.department}</Badge>
          )}
          <span className="text-sm text-muted-foreground">
            | Periode: {payroll.period.name}
          </span>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Main Content - Items Lists */}
        <div className="lg:col-span-2 space-y-6">
          {/* Earnings */}
          {renderItemsList(
            payroll.earnings,
            'earning',
            'Pendapatan',
            <TrendingUp className="h-5 w-5" />,
            'text-green-600 dark:text-green-500'
          )}

          {/* Deductions */}
          {renderItemsList(
            payroll.deductions,
            'deduction',
            'Potongan',
            <TrendingDown className="h-5 w-5" />,
            'text-red-600 dark:text-red-500'
          )}

          {/* Bonuses */}
          {renderItemsList(
            payroll.bonuses,
            'bonus',
            'Bonus',
            <Gift className="h-5 w-5" />,
            'text-blue-600 dark:text-blue-500'
          )}
        </div>

        {/* Summary Sidebar */}
        <div className="space-y-6">
          <Card className="sticky top-6">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Calculator className="h-5 w-5 text-primary" />
                Ringkasan
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-3">
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Total Pendapatan</span>
                  <span className="font-medium text-green-600 dark:text-green-500">
                    {formatCurrency(totals.earnings)}
                  </span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Total Bonus</span>
                  <span className="font-medium text-blue-600 dark:text-blue-500">
                    + {formatCurrency(totals.bonuses)}
                  </span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Total Potongan</span>
                  <span className="font-medium text-red-600 dark:text-red-500">
                    - {formatCurrency(totals.deductions)}
                  </span>
                </div>
              </div>

              <Separator />

              <div className="p-4 rounded-lg bg-primary/10">
                <div className="flex items-center gap-2 mb-2">
                  <DollarSign className="h-5 w-5 text-primary" />
                  <span className="font-medium">Gaji Bersih</span>
                </div>
                <p className="text-2xl font-bold text-primary">{formatCurrency(totals.net)}</p>
              </div>

              {/* Additional Info */}
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Jam Kerja</span>
                  <span>{payroll.worked_hours || 0} jam</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Lembur</span>
                  <span>{payroll.overtime_hours || 0} jam</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Status</span>
                  <Badge variant={payroll.status === 'draft' ? 'secondary' : 'default'}>
                    {payroll.status}
                  </Badge>
                </div>
              </div>

              <Separator />

              <div className="space-y-2">
                <Button
                  variant="outline"
                  className="w-full"
                  onClick={() => navigate({ to: '/admin/payroll' })}
                >
                  <ArrowLeft className="mr-2 h-4 w-4" />
                  Kembali ke Daftar
                </Button>
              </div>

              {payroll.notes && (
                <div className="p-3 rounded-lg bg-muted">
                  <p className="text-xs text-muted-foreground mb-1">Catatan:</p>
                  <p className="text-sm">{payroll.notes}</p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Add/Edit Item Dialog */}
      <Dialog open={itemDialogOpen} onOpenChange={setItemDialogOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              {editingItem ? 'Edit Item' : 'Tambah Item'}{' '}
              {itemForm.type === 'earning' ? 'Pendapatan' : itemForm.type === 'deduction' ? 'Potongan' : 'Bonus'}
            </DialogTitle>
          </DialogHeader>

          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label>Kategori</Label>
              <Select
                value={itemForm.category}
                onValueChange={(value) => setItemForm({ ...itemForm, category: value })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Pilih kategori" />
                </SelectTrigger>
                <SelectContent>
                  {categories?.[itemForm.type]?.map((cat) => (
                    <SelectItem key={cat.value} value={cat.value}>
                      {cat.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Deskripsi *</Label>
              <Input
                placeholder="Contoh: Tunjangan Transport"
                value={itemForm.description}
                onChange={(e) => setItemForm({ ...itemForm, description: e.target.value })}
              />
            </div>

            <div className="space-y-2">
              <Label>Jumlah (Rp) *</Label>
              <Input
                type="number"
                placeholder="0"
                value={itemForm.amount || ''}
                onChange={(e) => setItemForm({ ...itemForm, amount: Number(e.target.value) })}
              />
            </div>

            <div className="flex items-center justify-between">
              <Label htmlFor="is_taxable">Kena Pajak</Label>
              <Switch
                id="is_taxable"
                checked={itemForm.is_taxable}
                onCheckedChange={(checked) => setItemForm({ ...itemForm, is_taxable: checked })}
              />
            </div>

            <div className="space-y-2">
              <Label>Catatan</Label>
              <Textarea
                placeholder="Catatan tambahan (opsional)"
                value={itemForm.notes}
                onChange={(e) => setItemForm({ ...itemForm, notes: e.target.value })}
                rows={2}
              />
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setItemDialogOpen(false)}>
              Batal
            </Button>
            <Button
              onClick={handleSaveItem}
              disabled={createItemMutation.isPending || updateItemMutation.isPending}
            >
              {(createItemMutation.isPending || updateItemMutation.isPending) && (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              )}
              <Save className="mr-2 h-4 w-4" />
              Simpan
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <Dialog open={deleteConfirmOpen} onOpenChange={setDeleteConfirmOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Hapus Item</DialogTitle>
          </DialogHeader>
          <p className="text-sm text-muted-foreground py-4">
            Apakah Anda yakin ingin menghapus item "{itemToDelete?.description}"?
            Tindakan ini tidak dapat dibatalkan.
          </p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteConfirmOpen(false)}>
              Batal
            </Button>
            <Button
              variant="destructive"
              onClick={confirmDeleteItem}
              disabled={deleteItemMutation.isPending}
            >
              {deleteItemMutation.isPending && (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              )}
              <Trash2 className="mr-2 h-4 w-4" />
              Hapus
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
