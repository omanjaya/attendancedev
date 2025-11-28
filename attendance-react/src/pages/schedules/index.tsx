import { useState } from 'react';
import {
  Calendar,
  Layers,
  UserPlus,
  CalendarRange,
} from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

// Import tab contents
import { ScheduleListContent } from './tabs/ScheduleListContent';
import { ScheduleBuilderContent } from './tabs/ScheduleBuilderContent';
import { ScheduleAssignContent } from './tabs/ScheduleAssignContent';
import { MonthlyScheduleContent } from './tabs/MonthlyScheduleContent';

export default function SchedulesPage() {
  const [activeTab, setActiveTab] = useState('list');

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6">
        <div className="flex items-center gap-3 mb-2">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
            <Calendar className="h-5 w-5 text-primary" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-foreground">Manajemen Jadwal</h1>
            <p className="text-sm text-muted-foreground">
              Kelola jadwal kerja, penugasan, dan jadwal bulanan
            </p>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
        <TabsList className="grid w-full grid-cols-4 lg:w-auto lg:inline-flex">
          <TabsTrigger value="list" className="gap-2">
            <Calendar className="h-4 w-4" />
            <span className="hidden sm:inline">Daftar Jadwal</span>
            <span className="sm:hidden">Jadwal</span>
          </TabsTrigger>
          <TabsTrigger value="builder" className="gap-2">
            <Layers className="h-4 w-4" />
            <span className="hidden sm:inline">Builder</span>
            <span className="sm:hidden">Builder</span>
          </TabsTrigger>
          <TabsTrigger value="assign" className="gap-2">
            <UserPlus className="h-4 w-4" />
            <span className="hidden sm:inline">Penugasan</span>
            <span className="sm:hidden">Tugas</span>
          </TabsTrigger>
          <TabsTrigger value="monthly" className="gap-2">
            <CalendarRange className="h-4 w-4" />
            <span className="hidden sm:inline">Bulanan</span>
            <span className="sm:hidden">Bulan</span>
          </TabsTrigger>
        </TabsList>

        <TabsContent value="list" className="mt-6">
          <ScheduleListContent />
        </TabsContent>

        <TabsContent value="builder" className="mt-6">
          <ScheduleBuilderContent />
        </TabsContent>

        <TabsContent value="assign" className="mt-6">
          <ScheduleAssignContent />
        </TabsContent>

        <TabsContent value="monthly" className="mt-6">
          <MonthlyScheduleContent />
        </TabsContent>
      </Tabs>
    </div>
  );
}
