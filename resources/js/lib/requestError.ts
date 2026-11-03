import axios from 'axios';

/**
 * The message to show visitors when a request for map or venue data fails.
 *
 * Rate limits count per Cloudflare address, so a `429` usually means a lot of
 * people behind the same address are using the site at once, not that this
 * visitor did anything wrong.
 */
export function describeRequestError(error: unknown): string {
    return axios.isAxiosError(error) && error.response?.status === 429
        ? 'A lot of people are using the map right now. Please try again in a minute.'
        : "Couldn't load this. Please try again.";
}
