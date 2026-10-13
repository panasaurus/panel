import { Eye, EyeSlash, Key, Plus, TrashBin } from "@gravity-ui/icons";
import { format } from "date-fns";
import { Actions, useStoreActions } from "easy-peasy";
import { Field, Form, Formik, FormikHelpers } from "formik";
import { useCallback, useEffect, useState } from "react";
import { object, string } from "yup";

import FlashMessageRender from "@/components/FlashMessageRender";
import ActionButton from "@/components/elements/ActionButton";
import Code from "@/components/elements/Code";
import FormikFieldWrapper from "@/components/elements/FormikFieldWrapper";
import Input, { Textarea } from "@/components/elements/Input";
import SpinnerOverlay from "@/components/elements/SpinnerOverlay";
import { Dialog } from "@/components/elements/dialog";

import { createSSHKey, deleteSSHKey, useSSHKeys } from "@/api/account/ssh-keys";
import { httpErrorToHuman } from "@/api/http";

import { ApplicationStore } from "@/state";

import BlueprintBeforeContent from "@blueprint/components/Account/SSH/BeforeContent";
import BlueprintAfterContent from "@blueprint/components/Account/SSH/AfterContent";

interface CreateValues {
  name: string;
  publicKey: string;
}

const AccountSSHContainer = () => {
  const [deleteKey, setDeleteKey] = useState<{
    name: string;
    fingerprint: string;
  } | null>(null);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showKeys, setShowKeys] = useState<Record<string, boolean>>({});

  const {
    addError,
    clearFlashes,
    clearAndAddHttpError: flashHttpError,
  } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
  const { data, isValidating, error, mutate } = useSSHKeys({
    revalidateOnMount: true,
    revalidateOnFocus: false,
  });

  const clearAndAddHttpError = useCallback(
    (error?: Error | string | null) =>
      flashHttpError({ key: "account:ssh-keys", error }),
    [flashHttpError],
  );

  useEffect(() => {
    clearAndAddHttpError(error);
  }, [error, clearAndAddHttpError]);

  const doDeletion = () => {
    if (!deleteKey) return;

    clearAndAddHttpError();
    Promise.all([
      mutate(
        (data) =>
          data?.filter((value) => value.fingerprint !== deleteKey.fingerprint),
        false,
      ),
      deleteSSHKey(deleteKey.fingerprint),
    ])
      .catch((error) => {
        mutate(undefined, true).catch(console.error);
        clearAndAddHttpError(error);
      })
      .finally(() => {
        setDeleteKey(null);
      });
  };

  const submitCreate = (
    values: CreateValues,
    { setSubmitting, resetForm }: FormikHelpers<CreateValues>,
  ) => {
    clearFlashes("account:ssh-keys");
    createSSHKey(values.name, values.publicKey)
      .then((key) => {
        resetForm();
        setSubmitting(false);
        mutate((data) => (data || []).concat(key));
        setShowCreateModal(false);
      })
      .catch((error) => {
        console.error(error);
        addError({ key: "account:ssh-keys", message: httpErrorToHuman(error) });
        setSubmitting(false);
      });
  };

  const toggleKeyVisibility = (fingerprint: string) => {
    setShowKeys((prev) => ({
      ...prev,
      [fingerprint]: !prev[fingerprint],
    }));
  };

  return (
    <section
      className="account-settings-key-section"
      aria-labelledby="account-ssh-heading"
    >
      <BlueprintBeforeContent />
      <FlashMessageRender byKey="account:ssh-keys" />
      {showCreateModal && (
        <Dialog.Confirm
          open={showCreateModal}
          onClose={() => setShowCreateModal(false)}
          title="Add SSH key"
          confirm="Add Key"
          onConfirmed={() => {
            const form = document.getElementById(
              "create-ssh-form",
            ) as HTMLFormElement;
            if (form) {
              const submitButton = form.querySelector(
                'button[type="submit"]',
              ) as HTMLButtonElement;
              if (submitButton) submitButton.click();
            }
          }}
        >
          <Formik
            onSubmit={submitCreate}
            initialValues={{ name: "", publicKey: "" }}
            validationSchema={object().shape({
              name: string().required("SSH Key Name is required"),
              publicKey: string().required("Public Key is required"),
            })}
          >
            {({ isSubmitting }) => (
              <Form id="create-ssh-form" className="space-y-4">
                <SpinnerOverlay visible={isSubmitting} />

                <FormikFieldWrapper
                  label="SSH Key Name"
                  name="name"
                  description="A name to identify this SSH key."
                >
                  <Field name="name" as={Input} className="w-full" />
                </FormikFieldWrapper>

                <FormikFieldWrapper
                  label="Public Key"
                  name="publicKey"
                  description="Enter your public SSH key."
                >
                  <Field
                    name="publicKey"
                    as={Textarea}
                    rows={4}
                    spellCheck={false}
                    className="w-full"
                    placeholder="ssh-ed25519 AAAA…"
                  />
                </FormikFieldWrapper>

                <button type="submit" className="hidden" />
              </Form>
            )}
          </Formik>
        </Dialog.Confirm>
      )}

      <div className="account-settings-key-content">
        <div>
          <div className="account-settings-section-heading">
            <div>
              <h2 id="account-ssh-heading">SSH keys</h2>
              <p>
                Manage the public keys you use to connect to your servers over
                SFTP.
              </p>
            </div>
            <ActionButton
              variant="primary"
              onClick={() => setShowCreateModal(true)}
              className="flex items-center gap-2"
            >
              <Plus width={22} height={22} fill="currentColor" />
              Add SSH key
            </ActionButton>
          </div>
        </div>

        <div>
          <div className="account-settings-key-list">
            <SpinnerOverlay visible={!data && isValidating} />
            <Dialog.Confirm
              title={"Delete SSH key"}
              confirm={"Delete Key"}
              open={!!deleteKey}
              onClose={() => setDeleteKey(null)}
              onConfirmed={doDeletion}
            >
              Removing the <Code>{deleteKey?.name}</Code> SSH key will
              invalidate its usage across the Panel.
            </Dialog.Confirm>

            {!data || data.length === 0 ? (
              <div className="text-center py-12">
                <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-[#ffffff11] flex items-center justify-center">
                  <Key
                    width={22}
                    height={22}
                    className="text-zinc-400"
                    fill="currentColor"
                  />
                </div>
                <h3 className="text-lg font-medium text-zinc-200 mb-2">
                  No SSH keys
                </h3>
                <p className="text-sm text-zinc-400 max-w-sm mx-auto">
                  {!data
                    ? "Loading your SSH keys..."
                    : "You haven't added any SSH keys yet. Add one to securely access your servers."}
                </p>
              </div>
            ) : (
              <div className="space-y-3">
                {data.map((key) => (
                  <div
                    key={key.fingerprint}
                    className="account-settings-key-row"
                  >
                    <div className="account-settings-key-card">
                      <div className="flex items-center justify-between">
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-3 mb-2">
                            <h4 className="text-sm font-medium text-zinc-100 truncate">
                              {key.name}
                            </h4>
                          </div>
                          <div className="flex items-center gap-4 text-xs text-zinc-400">
                            <span>
                              Added:{" "}
                              {format(key.createdAt, "MMM d, yyyy HH:mm")}
                            </span>
                            <div className="flex items-center gap-2">
                              <span>Fingerprint:</span>
                              <code className="font-mono px-2 py-1 bg-[#ffffff08] border border-[#ffffff08] rounded text-zinc-300">
                                {showKeys[key.fingerprint]
                                  ? `SHA256:${key.fingerprint}`
                                  : "SHA256:••••••••••••••••"}
                              </code>
                              <ActionButton
                                variant="secondary"
                                size="sm"
                                aria-label={
                                  showKeys[key.fingerprint]
                                    ? "Hide key fingerprint"
                                    : "Show key fingerprint"
                                }
                                onClick={() =>
                                  toggleKeyVisibility(key.fingerprint)
                                }
                                className="p-1 text-zinc-400 hover:text-zinc-300"
                              >
                                {showKeys[key.fingerprint] ? (
                                  <EyeSlash
                                    width={18}
                                    height={18}
                                    fill="currentColor"
                                  />
                                ) : (
                                  <Eye
                                    width={18}
                                    height={18}
                                    fill="currentColor"
                                  />
                                )}
                              </ActionButton>
                            </div>
                          </div>
                        </div>
                        <ActionButton
                          variant="danger"
                          size="sm"
                          className="ml-4"
                          aria-label={`Delete SSH key ${key.name}`}
                          onClick={() =>
                            setDeleteKey({
                              name: key.name,
                              fingerprint: key.fingerprint,
                            })
                          }
                        >
                          <TrashBin
                            width={20}
                            height={20}
                            fill="currentColor"
                          />
                        </ActionButton>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
      <BlueprintAfterContent />
    </section>
  );
};

export default AccountSSHContainer;
