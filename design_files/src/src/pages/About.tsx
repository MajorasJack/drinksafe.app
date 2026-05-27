import { motion } from 'framer-motion';
import {
  ShieldAlertIcon,
  HeartIcon,
  InfoIcon,
  ExternalLinkIcon } from
'lucide-react';
import React from 'react';
export const About: React.FC = () => {
  return (
    <motion.div
      initial={{
        opacity: 0
      }}
      animate={{
        opacity: 1
      }}
      className="flex-grow bg-white py-12">

      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="mb-12 text-center">
          <h1 className="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
            About DrinkSafe
          </h1>
          <p className="text-lg text-slate-600 leading-relaxed">
            An independent platform dedicated to community awareness and safety.
          </p>
        </div>

        <div className="prose prose-slate max-w-none space-y-12">
          {/* Mission */}
          <section>
            <div className="flex items-center gap-3 mb-4">
              <div className="bg-brand-teal/10 p-2 rounded-lg text-brand-teal">
                <InfoIcon className="w-6 h-6" />
              </div>
              <h2 className="text-2xl font-bold text-slate-900 m-0">
                Our Purpose
              </h2>
            </div>
            <p className="text-slate-700 leading-relaxed">
              DrinkSafe was created to bridge the information gap regarding venue
              safety. While many incidents go unreported to authorities, victims
              often want to warn others. This platform provides an anonymous
              space to share these experiences, helping the community make
              informed decisions about where they spend their time.
            </p>
            <p className="text-slate-700 leading-relaxed mt-4">
              We believe that transparency encourages venues to take safety more
              seriously and empowers individuals to protect themselves and their
              friends.
            </p>
          </section>

          <hr className="border-slate-200" />

          {/* Police Guide */}
          <section id="police" className="scroll-mt-24">
            <div className="flex items-center gap-3 mb-6">
              <div className="bg-brand-amber/10 p-2 rounded-lg text-brand-amber-dark">
                <ShieldAlertIcon className="w-6 h-6" />
              </div>
              <h2 className="text-2xl font-bold text-slate-900 m-0">
                Reporting to Authorities
              </h2>
            </div>

            <div className="bg-slate-50 border border-slate-200 rounded-2xl p-6 sm:p-8">
              <p className="text-slate-800 font-medium mb-6">
                DrinkSafe is strictly informational. We do not forward reports to
                the police. Spiking is a serious crime, and we strongly
                encourage reporting incidents to the authorities.
              </p>

              <div className="grid sm:grid-cols-2 gap-6">
                <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                  <h3 className="font-bold text-slate-900 mb-2">
                    Emergency (999)
                  </h3>
                  <p className="text-sm text-slate-600 mb-4">
                    Call 999 immediately if the incident is happening now, the
                    suspect is still nearby, or someone is in immediate danger
                    or needs urgent medical attention.
                  </p>
                  <a
                    href="tel:999"
                    className="inline-block bg-brand-amber text-white font-medium px-4 py-2 rounded-lg text-sm hover:bg-brand-amber-light transition-colors">

                    Call 999
                  </a>
                </div>

                <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                  <h3 className="font-bold text-slate-900 mb-2">
                    Non-Emergency (101)
                  </h3>
                  <p className="text-sm text-slate-600 mb-4">
                    Call 101 if the incident has already happened, you are in a
                    safe place, and you do not need urgent medical attention.
                  </p>
                  <a
                    href="tel:101"
                    className="inline-block bg-slate-800 text-white font-medium px-4 py-2 rounded-lg text-sm hover:bg-slate-700 transition-colors">

                    Call 101
                  </a>
                </div>
              </div>
            </div>
          </section>

          <hr className="border-slate-200" />

          {/* Support Resources */}
          <section>
            <div className="flex items-center gap-3 mb-6">
              <div className="bg-rose-100 p-2 rounded-lg text-rose-600">
                <HeartIcon className="w-6 h-6" />
              </div>
              <h2 className="text-2xl font-bold text-slate-900 m-0">
                Support Resources
              </h2>
            </div>

            <p className="text-slate-700 mb-6">
              If you've been affected by spiking, you don't have to deal with it
              alone. These organizations offer free, confidential support:
            </p>

            <div className="space-y-4">
              <a
                href="#"
                className="flex items-center justify-between p-4 rounded-xl border border-slate-200 hover:border-brand-teal hover:shadow-sm transition-all group">

                <div>
                  <h4 className="font-bold text-slate-900 group-hover:text-brand-teal transition-colors">
                    Victim Support
                  </h4>
                  <p className="text-sm text-slate-500">
                    Free, confidential 24/7 support for victims of crime.
                  </p>
                </div>
                <ExternalLinkIcon className="w-5 h-5 text-slate-400 group-hover:text-brand-teal" />
              </a>

              <a
                href="#"
                className="flex items-center justify-between p-4 rounded-xl border border-slate-200 hover:border-brand-teal hover:shadow-sm transition-all group">

                <div>
                  <h4 className="font-bold text-slate-900 group-hover:text-brand-teal transition-colors">
                    Drinkaware
                  </h4>
                  <p className="text-sm text-slate-500">
                    Information and advice on alcohol and drink spiking.
                  </p>
                </div>
                <ExternalLinkIcon className="w-5 h-5 text-slate-400 group-hover:text-brand-teal" />
              </a>

              <a
                href="#"
                className="flex items-center justify-between p-4 rounded-xl border border-slate-200 hover:border-brand-teal hover:shadow-sm transition-all group">

                <div>
                  <h4 className="font-bold text-slate-900 group-hover:text-brand-teal transition-colors">
                    Stamp Out Spiking
                  </h4>
                  <p className="text-sm text-slate-500">
                    Charity dedicated to tackling spiking incidents globally.
                  </p>
                </div>
                <ExternalLinkIcon className="w-5 h-5 text-slate-400 group-hover:text-brand-teal" />
              </a>
            </div>
          </section>
        </div>
      </div>
    </motion.div>);

};
