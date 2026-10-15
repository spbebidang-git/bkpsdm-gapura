// components/Copyright.tsx
import { Link } from "@inertiajs/react";

export default function Copyright() {
    const currentYear = new Date().getFullYear();

    return (
        <div className="max-w-7xl mx-auto text-center text-sm text-white font-medium pt-4 xl:pt-8">
            Copyright © {currentYear}{" "}
            <Link href="/" className="font-bold">
                bkpsdmkabupatengresik
            </Link>. All Rights Reserved.
        </div>
    );
}
