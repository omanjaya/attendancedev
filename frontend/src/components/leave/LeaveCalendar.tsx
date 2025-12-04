import { useState } from 'react';
import {
    format,
    startOfMonth,
    endOfMonth,
    startOfWeek,
    endOfWeek,
    eachDayOfInterval,
    isSameMonth,
    isSameDay,
    addMonths,
    subMonths,
    isWithinInterval,
    parseISO,
} from 'date-fns';
import { id } from 'date-fns/locale';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { leaveTypeColors, type LeaveRequest } from '@/types/leave';

interface LeaveCalendarProps {
    leaveRequests: LeaveRequest[];
}

export function LeaveCalendar({ leaveRequests }: LeaveCalendarProps) {
    const [currentMonth, setCurrentMonth] = useState(new Date());

    const onPrevMonth = () => setCurrentMonth(subMonths(currentMonth, 1));
    const onNextMonth = () => setCurrentMonth(addMonths(currentMonth, 1));

    const monthStart = startOfMonth(currentMonth);
    const monthEnd = endOfMonth(monthStart);
    const startDate = startOfWeek(monthStart, { weekStartsOn: 1 }); // Monday start
    const endDate = endOfWeek(monthEnd, { weekStartsOn: 1 });

    const calendarDays = eachDayOfInterval({
        start: startDate,
        end: endDate,
    });

    const weekDays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

    // Filter approved leaves only for the calendar view usually, or all?
    // Let's show all but distinguish status if needed. Usually calendar shows approved schedule.
    // But admins might want to see pending too.
    // Let's filter for approved for now to avoid clutter, or show pending with transparency.
    const relevantLeaves = leaveRequests.filter(
        (req) => req.status === 'approved' || req.status === 'pending'
    );

    const getLeavesForDay = (day: Date) => {
        return relevantLeaves.filter((req) => {
            const start = parseISO(req.start_date);
            const end = parseISO(req.end_date);
            return isWithinInterval(day, { start, end });
        });
    };

    return (
        <Card className="h-full flex flex-col">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-4">
                <CardTitle className="text-lg font-medium">
                    {format(currentMonth, 'MMMM yyyy', { locale: id })}
                </CardTitle>
                <div className="flex items-center gap-1">
                    <Button variant="outline" size="icon" onClick={onPrevMonth}>
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <Button variant="outline" size="icon" onClick={onNextMonth}>
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                </div>
            </CardHeader>
            <CardContent className="flex-1">
                {/* Weekday Headers */}
                <div className="grid grid-cols-7 mb-2">
                    {weekDays.map((day) => (
                        <div
                            key={day}
                            className="text-center text-sm font-medium text-muted-foreground py-2"
                        >
                            {day}
                        </div>
                    ))}
                </div>

                {/* Calendar Grid */}
                <div className="grid grid-cols-7 gap-1 auto-rows-fr h-full">
                    {calendarDays.map((day) => {
                        const leaves = getLeavesForDay(day);
                        const isCurrentMonth = isSameMonth(day, monthStart);
                        const isToday = isSameDay(day, new Date());

                        return (
                            <div
                                key={day.toString()}
                                className={`
                  min-h-[100px] p-2 border rounded-md flex flex-col gap-1
                  ${!isCurrentMonth ? 'bg-muted/30 text-muted-foreground' : 'bg-background'}
                  ${isToday ? 'ring-2 ring-primary ring-inset' : ''}
                `}
                            >
                                <span
                                    className={`text-sm font-medium ${!isCurrentMonth ? 'text-muted-foreground' : ''
                                        }`}
                                >
                                    {format(day, 'd')}
                                </span>

                                <div className="flex flex-col gap-1 overflow-y-auto max-h-[80px] no-scrollbar">
                                    {leaves.map((leave) => (
                                        <TooltipProvider key={leave.id}>
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <div
                                                        className={`
                              text-[10px] px-1.5 py-0.5 rounded border truncate cursor-pointer flex items-center gap-1
                              ${leave.status === 'pending' ? 'opacity-70 border-dashed' : ''}
                            `}
                                                        style={{
                                                            backgroundColor: `${leaveTypeColors[leave.type || 'annual']}20`, // 20% opacity
                                                            borderColor: leaveTypeColors[leave.type || 'annual'],
                                                            color: leaveTypeColors[leave.type || 'annual'],
                                                        }}
                                                    >
                                                        <Avatar className="h-3 w-3">
                                                            <AvatarFallback className="text-[8px] bg-transparent">
                                                                {leave.employee_name?.substring(0, 1) || 'U'}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <span className="truncate">{leave.employee_name}</span>
                                                    </div>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <div className="text-xs">
                                                        <p className="font-semibold">{leave.employee_name}</p>
                                                        <p>{leave.type} • {leave.days_requested} Hari</p>
                                                        <p className="italic text-muted-foreground">{leave.reason}</p>
                                                    </div>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}
