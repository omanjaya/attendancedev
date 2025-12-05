import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { AcademicYearTab } from './components/AcademicYearTab';
import { EmployeeTypeTab } from './components/EmployeeTypeTab';
import { SubjectTab } from './components/SubjectTab';
import { ClassroomTab } from './components/ClassroomTab';
import { PeriodTab } from './components/PeriodTab';
import { PageHeader } from '@/components/shared/PageHeader';

export default function MasterDataPage() {
    return (
        <div className="space-y-6">
            <PageHeader
                title="Master Data"
                description="Kelola data referensi sistem seperti tahun ajaran, kelas, mata pelajaran, dll."
            />

            <Tabs defaultValue="academic-years" className="space-y-4">
                <TabsList>
                    <TabsTrigger value="academic-years">Tahun Ajaran</TabsTrigger>
                    <TabsTrigger value="employee-types">Jenis Pegawai</TabsTrigger>
                    <TabsTrigger value="subjects">Mata Pelajaran</TabsTrigger>
                    <TabsTrigger value="classrooms">Kelas</TabsTrigger>
                    <TabsTrigger value="periods">Periode Waktu</TabsTrigger>
                </TabsList>

                <TabsContent value="academic-years">
                    <Card>
                        <CardHeader>
                            <CardTitle>Tahun Ajaran</CardTitle>
                            <CardDescription>
                                Kelola tahun ajaran dan semester aktif.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <AcademicYearTab />
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="employee-types">
                    <Card>
                        <CardHeader>
                            <CardTitle>Jenis Pegawai</CardTitle>
                            <CardDescription>
                                Kelola tipe kepegawaian (Honor, Tetap, dll).
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <EmployeeTypeTab />
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="subjects">
                    <Card>
                        <CardHeader>
                            <CardTitle>Mata Pelajaran</CardTitle>
                            <CardDescription>
                                Daftar mata pelajaran yang diajarkan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <SubjectTab />
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="classrooms">
                    <Card>
                        <CardHeader>
                            <CardTitle>Kelas</CardTitle>
                            <CardDescription>
                                Daftar kelas dan rombongan belajar.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ClassroomTab />
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="periods">
                    <Card>
                        <CardHeader>
                            <CardTitle>Periode Waktu</CardTitle>
                            <CardDescription>
                                Atur jam pelajaran dan istirahat.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <PeriodTab />
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    );
}
