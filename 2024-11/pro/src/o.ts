
export function weekday(day) {
    switch (day) {
        case '0':
            return 'Niedziela';
            break;
        case '1':
            return 'Poniedziałek';
            break;
        case '2':
            return 'Wtorek';
            break;
        case '3':
            return 'Środa';
            break;
        case '4':
            return 'Czwartek';
            break;
        case '5':
            return 'Piątek';
            break;
        case '6':
            return 'Sobota';
            break;
        default:
            return '';
    }
}
