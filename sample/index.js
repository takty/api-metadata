/**
 * Functions for Retrieving Metadata of Websites
 *
 * @package api
 * @author Takuto Yanagida
 * @version 2024-02-06
 */

let apiUrl;

export function initialize(url) {
	apiUrl = url;
}

export async function getMetadata(url) {
	const ps = { url };
	return await fetchResponse(ps);
}

async function fetchResponse(ps) {
	try {
		const qs  = new URLSearchParams(ps).toString();
		const res = await fetch(`${apiUrl}?${qs}`);
		const d   = await res.text();
		return d ? JSON.parse(d) : [];
	} catch (e) {
		console.error(e);
		return [];
	}
}
