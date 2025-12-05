import { useState } from 'react';
import { useMasterData } from '@/hooks/use-master-data';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Edit, Trash2, Loader2, Search } from 'lucide-react';

export function SubjectTab() {
    const { subjects } = useMasterData();
    const [page] = useState(1);
    const [search, setSearch] = useState('');
    const { data, isLoading } = subjects.useQuery({ page, search });
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<any>(null);
    const [formData, setFormData] = useState({
        name: '',
        code: '',
        description: '',
    });

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            if (editingItem) {
                await subjects.update({ id: editingItem.id, data: formData });
            } else {
                await subjects.create(formData);
            }
            setIsDialogOpen(false);
            setEditingItem(null);
            setFormData({ name: '', code: '', description: '' });
        } catch (error) {
            console.error(error);
        }
    };

    const openEdit = (item: any) => {
        setEditingItem(item);
        setFormData({
            name: item.name,
            code: item.code,
            description: item.description || '',
        });
        setIsDialogOpen(true);
    };

    const handleDelete = async (id: string) => {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            await subjects.delete(id);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <div className="relative w-64">
                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Cari mata pelajaran..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="pl-8"
                    />
                </div>
                <Button onClick={() => {
                    setEditingItem(null);
                    setFormData({ name: '', code: '', description: '' });
                    setIsDialogOpen(true);
                }}>
                    <Plus className="mr-2 h-4 w-4" /> Tambah
                </Button>
            </div>

            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Kode</TableHead>
                            <TableHead>Nama</TableHead>
                            <TableHead>Deskripsi</TableHead>
                            <TableHead className="w-[100px]">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {isLoading ? (
                            <TableRow>
                                <TableCell colSpan={4} className="text-center py-8">
                                    <Loader2 className="h-6 w-6 animate-spin mx-auto" />
                                </TableCell>
                            </TableRow>
                        ) : data?.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={4} className="text-center py-8 text-muted-foreground">
                                    Tidak ada data
                                </TableCell>
                            </TableRow>
                        ) : (
                            data?.data.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-mono">{item.code}</TableCell>
                                    <TableCell className="font-medium">{item.name}</TableCell>
                                    <TableCell>{item.description || '-'}</TableCell>
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
                        <DialogTitle>{editingItem ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label>Kode</Label>
                            <Input
                                placeholder="Contoh: MAT"
                                value={formData.code}
                                onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Nama</Label>
                            <Input
                                placeholder="Contoh: Matematika"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Deskripsi</Label>
                            <Textarea
                                placeholder="Keterangan tambahan..."
                                value={formData.description}
                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
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
