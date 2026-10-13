import styled, { css } from 'styled-components';

export interface Props {
    isLight?: boolean;
    hasError?: boolean;
}

const checkboxStyle = css<Props>`
    appearance: none;
    width: 1rem;
    height: 1rem;
    border: 1px solid #3b3b43;
    border-radius: 0.25rem;
    background-color: #18181b;
    color-adjust: exact;
    background-origin: border-box;
    transition:
        all 75ms linear,
        box-shadow 25ms linear;

    &:checked {
        background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M5.707 7.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4a1 1 0 0 0-1.414-1.414L7 8.586 5.707 7.293z'/%3e%3c/svg%3e");
        background-color: #23d18d;
        border-color: #23d18d;
        background-size: 100% 100%;
        background-position: center;
        background-repeat: no-repeat;
    }

    &:hover {
        border-color: #52525b;
    }

    &:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(35, 209, 141, 0.10);
        border-color: #23d18d;
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
`;

const inputStyle = css<Props>`
    resize: none;

    &:not(:disabled):not(:read-only):focus {
        border-color: #23d18d;
        box-shadow: 0 0 0 3px rgba(35, 209, 141, 0.10);
    }

    &:disabled {
        background-color: #111113;
        color: #92929c;
        opacity: 0.8;
    }

    padding-left: 1rem;
    padding-right: 1rem;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    border-radius: 0.5rem;
    outline: none;
    border: 1px solid #3b3b43;
    background-color: #18181b;
    color: #f4f4f5;
    &::placeholder {
        color: #8d8d98 !important;
    }
    font-size: 0.875rem;
`;

const Input = styled.input<Props>`
    &:not([type='checkbox']):not([type='radio']) {
        ${inputStyle};
    }

    &[type='checkbox'],
    &[type='radio'] {
        ${checkboxStyle};
    }
`;
const Textarea = styled.textarea<Props>`
    ${inputStyle}
`;

export { Textarea };
export default Input;
