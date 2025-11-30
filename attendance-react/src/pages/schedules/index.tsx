import { useState } from 'react';
import {
  Calendar,
  Layers,
  UserPlus,
  CalendarRange,
  Download,
} from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';

import { ScheduleListContent } from './tabs/ScheduleListContent';
import { ScheduleBuilderContent } from './tabs/ScheduleBuilderContent';
import { ScheduleAssignContent } from './tabs/ScheduleAssignContent';
import { MonthlyScheduleContent } from './tabs/MonthlyScheduleContent';

export default function SchedulesPage() {
  const [activeTab, setActiveTab] = useState('list');

  return (
    <div className="space-y-6 p-6">
      {/* Hero Header */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary/10 via-primary/5 to-background p-8">
        <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-primary/10 blur-3xl" />
        <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-primary/5 blur-3xl" />

        <div className="relative">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3">
              <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                <Calendar className="h-6 w-6 text-primary" />
              </div>
              <div>
                <h1 className="text-2xl font-bold text-foreground">Manajemen Jadwal</h1>
                <p className="text-sm text-muted-foreground">
                  Kelola jadwal kerja, penugasan, dan jadwal bulanan
                </p>
              </div>
            </div>

            <Button variant="outline" size="sm" className="bg-background/50 backdrop-blur-sm">
              <Download className="mr-2 h-4 w-4" />
              Export
            </Button>
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
