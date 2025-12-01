import { FileText, BarChart2, Download } from 'lucide-react';
import { PageHeader } from '@/components/shared';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export function DesktopReportsPage() {
    return (
        <div className="space-y-6 p-6">
            <PageHeader
                title="Laporan"
                description="Pusat laporan dan analitik"
                icon={BarChart2}
            />
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            Laporan Kehadiran
                        </CardTitle>
                        <CardDescription>Rekap kehadiran karyawan bulanan</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button className="w-full">
                            <Download className="mr-2 h-4 w-4" />
                            Unduh PDF
                        </Button>
                    </CardContent>
                </Card>
                {/* Add more cards as needed */}
            </div>
        </div>
    );
}
