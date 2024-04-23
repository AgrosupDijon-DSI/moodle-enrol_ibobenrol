import {call as fetchMany} from 'core/ajax';

export const filterBadges = (
    userinput,
) => fetchMany([{
    methodname: 'enrol_ibobenrol_filter_badge_function',
    args: {
        userinput,
    },
}])[0];
export const init = () => fetchMany([{
    methodname: 'enrol_ibobenrol_filter_badge_function',
}])[0];
