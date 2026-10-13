import FlashMessageRender from '@/components/FlashMessageRender';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

type Props = Readonly<
    React.DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement> & {
        title?: string;
        borderColor?: string;
        showFlashes?: string | boolean;
        showLoadingOverlay?: boolean;
    }
>;

const ContentBox = ({ title, showFlashes, showLoadingOverlay, children, ...props }: Props) => (
    <div className='p-8 bg-[#101012] border-[1px] border-[#27272a] shadow-xs rounded-xl text-[#f4f4f5]' {...props}>
        {title && <h2 className={`font-extrabold mb-4  text-2xl`}>{title}</h2>}
        {showFlashes && <FlashMessageRender byKey={typeof showFlashes === 'string' ? showFlashes : undefined} />}
        <div>
            <SpinnerOverlay visible={showLoadingOverlay || false} />
            {children}
        </div>
    </div>
);

export default ContentBox;
