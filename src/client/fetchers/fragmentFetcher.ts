import { apiUrl } from "@/utils";
import { RequestError } from "@/errors";
import { DataListResponse, DataResponse, Fragment } from "@/types";

export async function fragmentIdsFetcher() {
    const res = await fetch(apiUrl(`fragments/ids`), {
        headers: {
            "Content-Type": "application/json",
            accept: "application/json",
        },
    });
    if (!res.ok) {
        const error = new RequestError("An error occurred while fetching the data.");
        error.info = await res.json();
        error.status = res.status;
        throw error;
    }
    return (await res.json()) as DataListResponse<number>;
}

export async function fragmentFetcher({ fragmentId: id }: { fragmentId: string | number }) {
    const res = await fetch(apiUrl(`fragments/${id}`), {
        headers: {
            "Content-Type": "application/json",
            accept: "application/json",
        },
    });
    if (!res.ok) {
        const error = new RequestError("An error occurred while fetching the data.");
        error.info = await res.json();
        error.status = res.status;
        throw error;
    }
    return (await res.json()) as DataResponse<Fragment>;
}
