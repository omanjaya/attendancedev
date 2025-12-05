import { useState } from 'react';
import { useMasterData } from '@/hooks/use-master-data';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Plus, Edit, Trash2, Loader2, Search } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

export function PeriodTab() {
    const { periods } = useMasterData();
    const [page] = useState(1);
    const [search, setSearch] = useState('');
    const { data, isLoading } = periods.useQuery({ page, search });
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<any>(null);
    const [formData, setFormData] = useState({
        name: '',
        start_time: '',
        end_time: '',
        is_break: false,
        order_index: 0,
    });

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            if (editingItem) {
                await periods.update({ id: editingItem.id, data: formData });
            } else {
                await periods.create(formData);
            }
            setIsDialogOpen(false);
            setEditingItem(null);
            setFormData({ name: '', start_time: '', end_time: '', is_break: false, order_index: 0 });
        } catch (error) {
            console.error(error);
        }
    };

    const openEdit = (item: any) => {
        setEditingItem(item);
        setFormData({
            name: item.name,
            start_time: item.start_time.substring(0, 5), // Format HH:mm
            end_time: item.end_time.substring(0, 5),
            is_break: item.is_break,
            order_index: item.order_index,
        });
        setIsDialogOpen(true);
    };

    const handleDelete = async (id: string) => {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            await periods.delete(id);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <div className="relative w-64">
                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Cari periode..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="pl-8"
                    />
                </div>
                <Button onClick={() => {
                    setEditingItem(null);
                    setFormData({ name: '', start_time: '', end_time: '', is_break: false, order_index: 0 });
                    setIsDialogOpen(true);
                }}>
                    <Plus className="mr-2 h-4 w-4" /> Tambah
                </Button>
            </div>

            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Urutan</TableHead>
                            <TableHead>Nama</TableHead>
                            <TableHead>Waktu Mulai</TableHead>
                            <TableHead>Waktu Selesai</TableHead>
                            <TableHead>Tipe</TableHead>
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
                                    <TableCell>{item.order_index}</TableCell>
                                    <TableCell className="font-medium">{item.name}</TableCell>
                                    <TableCell>{item.start_time.substring(0, 5)}</TableCell>
                                    <TableCell>{item.end_time.substring(0, 5)}</TableCell>
                                    <TableCell>
                                        {item.is_break ? (
                                            <Badge variant="secondary">Istirahat</Badge>
                                        ) : (
                                            <Badge variant="outline">Pelajaran</Badge>
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
                        <DialogTitle>{editingItem ? 'Edit Periode' : 'Tambah Periode'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label>Urutan</Label>
                            <Input
                                type="number"
                                value={formData.order_index}
                                onChange={(e) => setFormData({ ...formData, order_index: parseInt(e.target.value) })}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Nama Periode</Label>
                            <Input
                                placeholder="Contoh: Jam Ke-1"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                required
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>Waktu Mulai</Label>
                                <Input
                                    type="time"
                                    value={formData.start_time}
                                    onChange={(e) => setFormData({ ...formData, start_time: e.target.value })}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Waktu Selesai</Label>
                                <Input
                                    type="time"
                                    value={formData.end_time}
                                    onChange={(e) => setFormData({ ...formData, end_time: e.target.value })}
                                    required
                                />
                            </div>
                        </div>
                        <div className="flex items-center justify-between space-x-2">
                            <Label>Istirahat?</Label>
                            <Switch
                                checked={formData.is_break}
                                onCheckedChange={(checked) => setFormData({ ...formData, is_break: checked })}
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
