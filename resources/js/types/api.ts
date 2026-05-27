/**
 * API-related TypeScript types
 *
 * Generic types for API responses, pagination, and errors.
 */

/**
 * Standard pagination metadata
 */
export interface PaginationMeta {
    total: number;
    per_page?: number;
    current_page?: number;
    last_page?: number;
}

/**
 * Generic API response wrapper
 */
export interface ApiResponse<T> {
    data: T;
    meta?: PaginationMeta;
}

/**
 * API error response with validation errors
 */
export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
}
