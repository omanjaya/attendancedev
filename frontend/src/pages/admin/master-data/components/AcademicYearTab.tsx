import { useState } from 'react';
import { useMasterData } from '@/hooks/use-master-data';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Plus, Edit, Trash2, Loader2, Search } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

export function AcademicYearTab() {
    const { academicYears } = useMasterData();
    const [page] = useState(1);
    const [search, setSearch] = useState('');
    const { data, isLoading } = academicYears.useQuery({ page, search });
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<any>(null);
    const [formData, setFormData] = useState<{
        name: string;
        start_date: string;
        end_date: string;
        semester: 'odd' | 'even';
        is_active: boolean;
    }>({
        name: '',
        start_date: '',
        end_date: '',
        semester: 'odd',
        is_active: false,
    });

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            if (editingItem) {
                await academicYears.update({ id: editingItem.id, data: formData });
            } else {
                await academicYears.create(formData);
            }
            setIsDialogOpen(false);
            setEditingItem(null);
            setFormData({ name: '', start_date: '', end_date: '', semester: 'odd', is_active: false });
        } catch (error) {
            console.error(error);
        }
    };

    const openEdit = (item: any) => {
        setEditingItem(item);
        setFormData({
            name: item.name,
            start_date: item.start_date,
            end_date: item.end_date,
            semester: item.semester,
            is_active: item.is_active,
        });
        setIsDialogOpen(true);
    };

    const handleDelete = async (id: string) => {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            await academicYears.delete(id);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <div className="relative w-64">
                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Cari tahun ajaran..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="pl-8"
                    />
                </div>
                <Button onClick={() => {
                    setEditingItem(null);
                    setFormData({ name: '', start_date: '', end_date: '', semester: 'odd', is_active: false });
                    setIsDialogOpen(true);
                }}>
                    <Plus className="mr-2 h-4 w-4" /> Tambah
                </Button>
            </div>

            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nama</TableHead>
                            <TableHead>Semester</TableHead>
                            <TableHead>Mulai</TableHead>
                            <TableHead>Selesai</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="w-[100px]">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {isLoading ? (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center py-8">
                                    <Loader2 className="h-6 w-6 animate-spin mx-auto" />
                                </TableCell>
                            </TableRow>
                        ) : data?.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                                    Tidak ada data
                                </TableCell>
                            </TableRow>
                        ) : (
                            data?.data.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">{item.name}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline">
                                            {item.semester === 'odd' ? 'Ganjil' : 'Genap'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{new Date(item.start_date).toLocaleDateString('id-ID')}</TableCell>
                                    <TableCell>{new Date(item.end_date).toLocaleDateString('id-ID')}</TableCell>
                                    <TableCell>
                                        {item.is_active ? (
                                            <Badge className="bg-green-500">Aktif</Badge>
                                        ) : (
                                            <Badge variant="secondary">Tidak Aktif</Badge>
                                        )}
                                    </TableCell>
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
                        <DialogTitle>{editingItem ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label>Nama Tahun Ajaran</Label>
                            <Input
                                placeholder="Contoh: 2024/2025"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                required
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>Tanggal Mulai</Label>
                                <Input
                                    type="date"
                                    value={formData.start_date}
                                    onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Tanggal Selesai</Label>
                                <Input
                                    type="date"
                                    value={formData.end_date}
                                    onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                                    required
                                />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label>Semester</Label>
                            <Select
                                value={formData.semester}
                                onValueChange={(value: 'odd' | 'even') => setFormData({ ...formData, semester: value })}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="odd">Ganjil</SelectItem>
                                    <SelectItem value="even">Genap</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="flex items-center justify-between space-x-2">
                            <Label>Status Aktif</Label>
                            <Switch
                                checked={formData.is_active}
                                onCheckedChange={(checked) => setFormData({ ...formData, is_active: checked })}
                            />
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
