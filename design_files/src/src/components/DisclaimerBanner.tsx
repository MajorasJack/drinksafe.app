import React from 'react';
import { InfoIcon } from 'lucide-react';
interface DisclaimerBannerProps {
  className?: string;
}
export const DisclaimerBanner: React.FC<DisclaimerBannerProps> = ({
  className = ''
}) => {
  return (
    <div
      className={`bg-slate-100 border border-slate-200 rounded-xl p-4 sm:p-5 flex gap-4 items-start ${className}`}>
      
      <div className="bg-slate-200 text-slate-600 p-2 rounded-full shrink-0 mt-0.5">
        <InfoIcon className="w-5 h-5" />
      </div>
      <div>
        <h4 className="font-semibold text-slate-900 mb-1">
          Informational Only
        </h4>
        <p className="text-sm text-slate-600 leading-relaxed">
          This platform is designed to share community knowledge and help people
          make informed decisions.
          <strong>
            {' '}
            Submitting a report here does not notify the police or the venue.
          </strong>{' '}
          If you wish to report a crime, please contact the police on 101
          (non-emergency) or 999 (emergency).
        </p>
      </div>
    </div>);

};