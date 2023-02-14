import { forwardRef, useEffect, useImperativeHandle, useRef/* ts-begin */, InputHTMLAttributes/* ts-end */ } from 'react';

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props }/* ts-begin */: InputHTMLAttributes<HTMLInputElement> & { isFocused?: boolean }/* ts-end */,
    ref
) {
    const localRef = useRef/* ts-begin */<HTMLInputElement>/* ts-end */(null)

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }))

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, []);

    return (
        <div className="flex flex-col items-start">
            <input
                {...props}
                type={type}
                className={
                    'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm ' +
                    className
                }
                ref={localRef}
            />
        </div>
    );
});
