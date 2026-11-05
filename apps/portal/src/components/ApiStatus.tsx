"use client";

import { useEffect, useState } from "react";

export function ApiStatus() {
  const [status, setStatus] = useState("checking");

  useEffect(() => {
    fetch("/api/v1/health")
      .then(async (response) =>
        setStatus(response.ok ? (await response.json()).status : "error"),
      )
      .catch(() => setStatus("error"));
  }, []);

  return <p>API : {status}</p>;
}
