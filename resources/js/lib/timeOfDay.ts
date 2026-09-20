import { TimeOfDay } from '@/types/report';

/**
 * Maps a 24-hour clock time ("HH:MM") onto the TimeOfDay bands shown in the
 * report form: Morning 6am-12pm, Afternoon 12pm-6pm, Evening 6pm-10pm,
 * Night 10pm-6am. Anything unparseable falls back to Unknown.
 */
export function timeOfDayFromTime(time: string): TimeOfDay {
    const hour = Number.parseInt(time.split(':')[0] ?? '', 10);

    if (Number.isNaN(hour) || hour < 0 || hour > 23) {
        return TimeOfDay.Unknown;
    }

    if (hour >= 6 && hour < 12) {
        return TimeOfDay.Morning;
    }

    if (hour >= 12 && hour < 18) {
        return TimeOfDay.Afternoon;
    }

    if (hour >= 18 && hour < 22) {
        return TimeOfDay.Evening;
    }

    return TimeOfDay.Night;
}
