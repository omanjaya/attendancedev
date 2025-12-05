import { useState } from 'react';
import { useMasterData } from '@/hooks/use-master-data';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Edit, Trash2, Loader2, Search } from 'lucide-react';

export function ClassroomTab() {
    const { classrooms, academicYears } = useMasterData();
    const [page] = useState(1);
    const [search, setSearch] = useState('');
    const { data, isLoading } = classrooms.useQuery({ page, search });
    const { data: academicYearsData } = academicYears.useQuery({ is_active: true }); // Get active academic years

    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<any>(null);
    const [formData, setFormData] = useState({
        name: '',
        grade_level: 10,
        major: '',
        academic_year_id: '',
    });

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            if (editingItem) {
                await classrooms.update({ id: editingItem.id, data: formData });
            } else {
                await classrooms.create(formData);
            }
            setIsDialogOpen(false);
            setEditingItem(null);
            setFormData({ name: '', grade_level: 10, major: '', academic_year_id: '' });
        } catch (error) {
            console.error(error);
        }
    };

    const openEdit = (item: any) => {
        setEditingItem(item);
        setFormData({
            name: item.name,
            grade_level: item.grade_level,
            major: item.major || '',
            academic_year_id: item.academic_year_id,
        });
        setIsDialogOpen(true);
    };

    const handleDelete = async (id: string) => {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            await classrooms.delete(id);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <div className="relative w-64">
                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Cari kelas..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="pl-8"
                    />
                </div>
                <Button onClick={() => {
                    setEditingItem(null);
                    // Set default academic year if available
                    const defaultAcademicYear = academicYearsData?.data.find((ay: any) => ay.is_active)?.id || '';
                    setFormData({ name: '', grade_level: 10, major: '', academic_year_id: defaultAcademicYear });
                    setIsDialogOpen(true);
                }}>
                    <Plus className="mr-2 h-4 w-4" /> Tambah
                </Button>
            </div>

            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Tingkat</TableHead>
                            <TableHead>Nama Kelas</TableHead>
                            <TableHead>Jurusan</TableHead>
                            <TableHead>Tahun Ajaran</TableHead>
                            <TableHead className="w-[100px]">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {isLoading ? (
                            <TableRow>
                                <TableCell colSpan={5} className="text-center py-8">
                                    <Loader2 className="h-6 w-6 animate-spin mx-auto" />
                                </TableCell>
                            </TableRow>
                        ) : data?.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                                    Tidak ada data
                                </TableCell>
                            </TableRow>
                        ) : (
                            data?.data.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell>{item.grade_level}</TableCell>
                                    <TableCell className="font-medium">{item.name}</TableCell>
                                    <TableCell>{item.major || '-'}</TableCell>
                                    <TableCell>{item.academic_year?.name || '-'}</TableCell>
                                    <TableCell>
                                        <div className="flex gap-2">
                                            <Button variant="ghost" size="icon" onClick={() => openEdit(item)}>
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" className="text-destructive" onClick={() => handleDelete(item.id)}>
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editingItem ? 'Edit Kelas' : 'Tambah Kelas'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>Tingkat</Label>
                                <Input
                                    type="number"
                                    min="1"
                                    value={formData.grade_level}
                                    onChange={(e) => setFormData({ ...formData, grade_level: parseInt(e.target.value) })}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Jurusan</Label>
                                <Input
                                    placeholder="Contoh: IPA"
                                    value={formData.major}
                                    onChange={(e) => setFormData({ ...formData, major: e.target.value })}
                                />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label>Nama Kelas</Label>
                            <Input
                                placeholder="Contoh: X IPA 1"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Tahun Ajaran</Label>
                            <Select
                                value={formData.academic_year_id}
                                onValueChange={(value) => setFormData({ ...formData, academic_year_id: value })}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Tahun Ajaran" />
                                </SelectTrigger>
                                <SelectContent>
                                    {academicYearsData?.data.map((ay: any) => (
                                        <SelectItem key={ay.id} value={ay.id}>
                                            {ay.name} ({ay.semester === 'odd' ? 'Ganjil' : 'Genap'})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <DialogFooter>
                            <Button type="submit">Simpan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
