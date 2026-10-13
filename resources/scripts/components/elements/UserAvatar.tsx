import Avatar from "boring-avatars";
import { useEffect, useState } from "react";

import type { UserData } from "@/state/user";

const colors = ["#09090b", "#0d392d", "#17875d", "#23d18d", "#a7f3d0"];

const UserAvatar = ({
  user,
}: {
  user: Pick<UserData, "uuid" | "email" | "image">;
}) => {
  const [source, setSource] = useState(user.image);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    let active = true;
    setSource(user.image);
    setFailed(false);
    if (user.image && window.crypto?.subtle) {
      const email = new TextEncoder().encode(user.email.trim().toLowerCase());
      window.crypto.subtle
        .digest("SHA-256", email)
        .then((digest) => {
          if (!active) return;
          const hash = Array.from(new Uint8Array(digest), (byte) =>
            byte.toString(16).padStart(2, "0"),
          ).join("");
          setSource(`https://gravatar.com/avatar/${hash}?s=160&d=blank`);
          setFailed(false);
        })
        .catch(() => {});
    }
    return () => {
      active = false;
    };
  }, [user.email, user.image]);

  return (
    <span className="client-avatar-visual" aria-hidden="true">
      <Avatar
        name={user.uuid}
        variant="marble"
        colors={colors}
        size={56}
        square
      />
      {source && !failed && (
        <img
          className="client-avatar-image"
          src={source}
          alt=""
          width={56}
          height={56}
          referrerPolicy="no-referrer"
          onError={() => setFailed(true)}
        />
      )}
    </span>
  );
};

export default UserAvatar;
