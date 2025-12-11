import { useState } from 'react';
import {
  Calculator,
  Plus,
  Search,
  Pencil,
  Trash2,
  Loader2,
  ToggleLeft,
  ToggleRight,
  Eye,
  TrendingUp,
  TrendingDown,
  Gift,
  ArrowLeft,
} from 'lucide-react';
import { Link } from '@tanstack/react-router';
import { PageHeader } from '@/components/shared';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogDescription,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { toast } from 'sonner';
import {
  usePayrollFormulas,
  useFormulaConfig,
  useCreateFormula,
  useUpdateFormula,
  useDeleteFormula,
  useToggleFormulaStatus,
  usePreviewFormula,
} from '@/hooks/use-payroll';
import type {
  PayrollFormula,
  PayrollFormulaInput,
  FormulaType,
  FormulaCalculationType,
} from '@/lib/api/payroll-formulas';

interface FormulFormData {
  name: string;
  code: string;
  type: FormulaType;
  formula_type: FormulaCalculationType;
  base_field: string;
  default_amount: number;
  percentage_rate: number;
  category: string;
  is_taxable: boolean;
  is_active: boolean;
  priority: number;
  description: string;
}

const defaultFormData: FormulFormData = {
  name: '',
  code: '',
  type: 'earning',
  formula_type: 'fixed',
  base_field: 'base_salary',
  default_amount: 0,
  percentage_rate: 0,
  category: 'allowance',
  is_taxable: true,
  is_active: true,
  priority: 0,
  description: '',
};

export default function PayrollFormulasPage() {
  const [typeFilter, setTypeFilter] = useState<FormulaType | 'all'>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingFormula, setEditingFormula] = useState<PayrollFormula | null>(null);
  const [formData, setFormData] = useState<FormulFormData>(defaultFormData);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [formulaToDelete, setFormulaToDelete] = useState<PayrollFormula | null>(null);
  const [previewDialogOpen, setPreviewDialogOpen] = useState(false);
  const [previewResult, setPreviewResult] = useState<{
    result: number;
    formatted_result: string;
  } | null>(null);

  const { data, isLoading } = usePayrollFormulas({
    type: typeFilter !== 'all' ? typeFilter : undefined,
    search: searchQuery || undefined,
  });
  const { data: config } = useFormulaConfig();

  const createMutation = useCreateFormula();
  const updateMutation = useUpdateFormula();
  const deleteMutation = useDeleteFormula();
  const toggleMutation = useToggleFormulaStatus();
  const previewMutation = usePreviewFormula();

  const formulas = data?.formulas ?? [];

  const getTypeIcon = (type: FormulaType) => {
    switch (type) {
      case 'earning':
        return <TrendingUp className="h-4 w-4 text-green-600" />;
      case 'deduction':
        return <TrendingDown className="h-4 w-4 text-red-600" />;
      case 'bonus':
        return <Gift className="h-4 w-4 text-blue-600" />;
    }
  };

  const getTypeBadgeVariant = (type: FormulaType): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (type) {
      case 'earning':
        return 'default';
      case 'deduction':
        return 'destructive';
      case 'bonus':
        return 'secondary';
    }
  };

  const openCreateDialog = () => {
    setEditingFormula(null);
    setFormData(defaultFormData);
    setDialogOpen(true);
  };

  const openEditDialog = (formula: PayrollFormula) => {
    setEditingFormula(formula);
    setFormData({
      name: formula.name,
      code: formula.code,
      type: formula.type,
      formula_type: formula.formula_type,
      base_field: formula.base_field || 'base_salary',
      default_amount: Number(formula.default_amount) || 0,
      percentage_rate: Number(formula.percentage_rate) || 0,
      category: formula.category || 'allowance',
      is_taxable: formula.is_taxable,
      is_active: formula.is_active,
      priority: formula.priority,
      description: formula.description || '',
    });
    setDialogOpen(true);
  };

  const handleSave = async () => {
    if (!formData.name.trim()) {
      toast.error('Nama formula wajib diisi');
      return;
    }
    if (!formData.code.trim()) {
      toast.error('Kode formula wajib diisi');
      return;
    }

    const payload: PayrollFormulaInput = {
      name: formData.name.trim(),
      code: formData.code.trim().toLowerCase().replace(/\s+/g, '_'),
      type: formData.type,
      formula_type: formData.formula_type,
      base_field: formData.formula_type === 'percentage' ? formData.base_field : null,
      default_amount: formData.formula_type === 'fixed' ? formData.default_amount : 0,
      percentage_rate: formData.formula_type === 'percentage' ? formData.percentage_rate : null,
      category: formData.category,
      is_taxable: formData.is_taxable,
      is_active: formData.is_active,
      priority: formData.priority,
      description: formData.description.trim() || null,
    };

    if (editingFormula) {
      updateMutation.mutate(
        { id: editingFormula.id, data: payload },
        {
          onSuccess: () => {
            setDialogOpen(false);
            setEditingFormula(null);
          },
        }
      );
    } else {
      createMutation.mutate(payload, {
        onSuccess: () => {
          setDialogOpen(false);
        },
      });
    }
  };

  const handleDelete = (formula: PayrollFormula) => {
    setFormulaToDelete(formula);
    setDeleteDialogOpen(true);
  };

  const confirmDelete = () => {
    if (!formulaToDelete) return;
    deleteMutation.mutate(formulaToDelete.id, {
      onSuccess: () => {
        setDeleteDialogOpen(false);
        setFormulaToDelete(null);
      },
    });
  };

  const handleToggleStatus = (formula: PayrollFormula) => {
    toggleMutation.mutate(formula.id);
  };

  const handlePreview = async (formula: PayrollFormula) => {
    previewMutation.mutate(
      { id: formula.id },
      {
        onSuccess: (result) => {
          setPreviewResult({
            result: result.result,
            formatted_result: result.formatted_result,
          });
          setEditingFormula(formula);
          setPreviewDialogOpen(true);
        },
      }
    );
  };

  const getCategoryOptions = () => {
    if (!config) return [];
    return config.categories[formData.type] || [];
  };

  return (
    <div className="space-y-6 p-4 sm:p-6">
      <div className="flex items-center gap-4 mb-4">
        <Link
          to="/admin/payroll"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke Payroll
        </Link>
      </div>

      <PageHeader
        title="Template Formula Payroll"
        description="Kelola template formula untuk perhitungan gaji otomatis"
        icon={Calculator}
        actions={
          <Button onClick={openCreateDialog}>
            <Plus className="h-4 w-4 mr-2" />
            Tambah Formula
          </Button>
        }
      />

      {/* Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Cari formula..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-9"
              />
            </div>
            <Tabs
              value={typeFilter}
              onValueChange={(v) => setTypeFilter(v as FormulaType | 'all')}
            >
              <TabsList>
                <TabsTrigger value="all">Semua</TabsTrigger>
                <TabsTrigger value="earning">Pendapatan</TabsTrigger>
                <TabsTrigger value="deduction">Potongan</TabsTrigger>
                <TabsTrigger value="bonus">Bonus</TabsTrigger>
              </TabsList>
            </Tabs>
          </div>
        </CardContent>
      </Card>

      {/* Formulas List */}
      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
        </div>
      ) : formulas.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center">
            <Calculator className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
            <h3 className="text-lg font-medium mb-2">Belum ada formula</h3>
            <p className="text-sm text-muted-foreground mb-4">
              Buat template formula untuk mempermudah perhitungan gaji
            </p>
            <Button onClick={openCreateDialog}>
              <Plus className="h-4 w-4 mr-2" />
              Tambah Formula Pertama
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {formulas.map((formula) => (
            <Card key={formula.id} className={!formula.is_active ? 'opacity-60' : ''}>
              <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-2">
                    {getTypeIcon(formula.type)}
                    <div>
                      <CardTitle className="text-base">{formula.name}</CardTitle>
                      <p className="text-xs text-muted-foreground font-mono">{formula.code}</p>
                    </div>
                  </div>
                  <Badge variant={getTypeBadgeVariant(formula.type)}>
                    {formula.type_label}
                  </Badge>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="p-3 rounded-lg bg-muted/50">
                  <p className="text-sm font-medium">{formula.formula_display}</p>
                  <p className="text-xs text-muted-foreground mt-1">
                    Tipe: {formula.formula_type_label}
                  </p>
                </div>

                <div className="flex flex-wrap gap-2">
                  {formula.is_taxable && (
                    <Badge variant="outline" className="text-xs">Kena Pajak</Badge>
                  )}
                  {!formula.is_active && (
                    <Badge variant="secondary" className="text-xs">Nonaktif</Badge>
                  )}
                  <Badge variant="outline" className="text-xs">
                    Prioritas: {formula.priority}
                  </Badge>
                </div>

                {formula.description && (
                  <p className="text-xs text-muted-foreground line-clamp-2">
                    {formula.description}
                  </p>
                )}

                <Separator />

                <div className="flex items-center justify-between">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => handleToggleStatus(formula)}
                    disabled={toggleMutation.isPending}
                  >
                    {formula.is_active ? (
                      <>
                        <ToggleRight className="h-4 w-4 mr-1 text-green-600" />
                        Aktif
                      </>
                    ) : (
                      <>
                        <ToggleLeft className="h-4 w-4 mr-1" />
                        Nonaktif
                      </>
                    )}
                  </Button>
                  <div className="flex gap-1">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handlePreview(formula)}
                      title="Preview"
                    >
                      <Eye className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => openEditDialog(formula)}
                      title="Edit"
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-destructive hover:text-destructive"
                      onClick={() => handleDelete(formula)}
                      title="Hapus"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Create/Edit Dialog */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>
              {editingFormula ? 'Edit Formula' : 'Tambah Formula Baru'}
            </DialogTitle>
            <DialogDescription>
              {editingFormula
                ? 'Perbarui konfigurasi formula yang sudah ada'
                : 'Buat template formula baru untuk perhitungan gaji'}
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4 py-4 max-h-[60vh] overflow-y-auto">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Nama Formula *</Label>
                <Input
                  placeholder="Tunjangan Transport"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label>Kode *</Label>
                <Input
                  placeholder="transport_allowance"
                  value={formData.code}
                  onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                  className="font-mono"
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Tipe</Label>
                <Select
                  value={formData.type}
                  onValueChange={(v) => setFormData({ ...formData, type: v as FormulaType })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="earning">Pendapatan</SelectItem>
                    <SelectItem value="deduction">Potongan</SelectItem>
                    <SelectItem value="bonus">Bonus</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Kategori</Label>
                <Select
                  value={formData.category}
                  onValueChange={(v) => setFormData({ ...formData, category: v })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {getCategoryOptions().map((cat) => (
                      <SelectItem key={cat.value} value={cat.value}>
                        {cat.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="space-y-2">
              <Label>Jenis Perhitungan</Label>
              <Select
                value={formData.formula_type}
                onValueChange={(v) =>
                  setFormData({ ...formData, formula_type: v as FormulaCalculationType })
                }
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="fixed">Nilai Tetap</SelectItem>
                  <SelectItem value="percentage">Persentase</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {formData.formula_type === 'fixed' && (
              <div className="space-y-2">
                <Label>Nilai Default (Rp)</Label>
                <Input
                  type="number"
                  value={formData.default_amount || ''}
                  onChange={(e) =>
                    setFormData({ ...formData, default_amount: Number(e.target.value) })
                  }
                />
              </div>
            )}

            {formData.formula_type === 'percentage' && (
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Persentase (%)</Label>
                  <Input
                    type="number"
                    step="0.01"
                    value={formData.percentage_rate || ''}
                    onChange={(e) =>
                      setFormData({ ...formData, percentage_rate: Number(e.target.value) })
                    }
                  />
                </div>
                <div className="space-y-2">
                  <Label>Basis Perhitungan</Label>
                  <Select
                    value={formData.base_field}
                    onValueChange={(v) => setFormData({ ...formData, base_field: v })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {config &&
                        Object.entries(config.base_fields).map(([value, label]) => (
                          <SelectItem key={value} value={value}>
                            {label}
                          </SelectItem>
                        ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>
            )}

            <div className="space-y-2">
              <Label>Prioritas</Label>
              <Input
                type="number"
                value={formData.priority}
                onChange={(e) => setFormData({ ...formData, priority: Number(e.target.value) })}
              />
              <p className="text-xs text-muted-foreground">
                Semakin tinggi angka, semakin belakangan dihitung
              </p>
            </div>

            <div className="space-y-2">
              <Label>Deskripsi</Label>
              <Textarea
                placeholder="Deskripsi formula (opsional)"
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                rows={2}
              />
            </div>

            <div className="flex items-center justify-between">
              <Label>Kena Pajak</Label>
              <Switch
                checked={formData.is_taxable}
                onCheckedChange={(v) => setFormData({ ...formData, is_taxable: v })}
              />
            </div>

            <div className="flex items-center justify-between">
              <Label>Aktif</Label>
              <Switch
                checked={formData.is_active}
                onCheckedChange={(v) => setFormData({ ...formData, is_active: v })}
              />
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>
              Batal
            </Button>
            <Button
              onClick={handleSave}
              disabled={createMutation.isPending || updateMutation.isPending}
            >
              {(createMutation.isPending || updateMutation.isPending) && (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              )}
              {editingFormula ? 'Perbarui' : 'Simpan'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Hapus Formula</DialogTitle>
          </DialogHeader>
          <p className="text-sm text-muted-foreground py-4">
            Apakah Anda yakin ingin menghapus formula "{formulaToDelete?.name}"?
            Tindakan ini tidak dapat dibatalkan.
          </p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteDialogOpen(false)}>
              Batal
            </Button>
            <Button
              variant="destructive"
              onClick={confirmDelete}
              disabled={deleteMutation.isPending}
            >
              {deleteMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              <Trash2 className="mr-2 h-4 w-4" />
              Hapus
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Preview Dialog */}
      <Dialog open={previewDialogOpen} onOpenChange={setPreviewDialogOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Preview Formula</DialogTitle>
            <DialogDescription>{editingFormula?.name}</DialogDescription>
          </DialogHeader>
          <div className="py-4">
            <div className="p-4 rounded-lg bg-muted/50 text-center">
              <p className="text-sm text-muted-foreground mb-2">Hasil Perhitungan</p>
              <p className="text-2xl font-bold text-primary">
                {previewResult?.formatted_result || '-'}
              </p>
            </div>
            <div className="mt-4 p-3 rounded border bg-card">
              <p className="text-xs text-muted-foreground mb-2">Dengan konteks default:</p>
              <ul className="text-xs space-y-1">
                <li>Gaji Pokok: Rp 5.000.000</li>
                <li>Gaji Kotor: Rp 6.000.000</li>
                <li>Hari Kerja: 22 hari</li>
                <li>Jam Lembur: 10 jam</li>
                <li>Tingkat Kehadiran: 95%</li>
              </ul>
            </div>
          </div>
          <DialogFooter>
            <Button onClick={() => setPreviewDialogOpen(false)}>Tutup</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
